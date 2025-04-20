<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use FibonacciService\Fibonacci;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessorBuilder;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

$otelEndpoint    = getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://jaeger:4318';
$serviceName     = getenv('OTEL_SERVICE_NAME')           ?: 'fibonacci-php';
$otlpUrl         = $otelEndpoint . '/v1/traces';

$transport       = (new OtlpHttpTransportFactory())->create($otlpUrl, 'application/x-protobuf');
$exporter        = new SpanExporter($transport);
$resource        = ResourceInfo::create(Attributes::create([
    ResourceAttributes::SERVICE_NAME => $serviceName,
]));
$processor       = (new BatchSpanProcessorBuilder($exporter))->build();
$tracerProvider  = TracerProvider::builder()
    ->addSpanProcessor($processor)
    ->setResource($resource)
    ->build();

register_shutdown_function([$tracerProvider, 'shutdown']);

$tracer = $tracerProvider->getTracer($serviceName, '1.0.0');

if (!isset($_GET['n'])) {
    http_response_code(400);
    echo "Error: Missing 'n' parameter";
    exit;
}

$nParam = $_GET['n'];
if (!ctype_digit($nParam)) {
    http_response_code(400);
    echo "Error: Parameter 'n' must be a non-negative integer";
    exit;
}

$n = (int) $nParam;
$rootSpan = $tracer->spanBuilder("HTTP GET /?n={$n}")
    ->setSpanKind(SpanKind::KIND_SERVER)
    ->startSpan();
$scope = $rootSpan->activate();

try {
    $fibonacci = new Fibonacci($tracer);
    $result = $fibonacci->calculate($n);

    $rootSpan
        ->setAttribute('parameter', $n)
        ->setAttribute('result', $result)
        ->setStatus(StatusCode::STATUS_OK);

    header('Content-Type: text/plain');
    echo $result . "\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Error: {$e->getMessage()}";
    $rootSpan
        ->recordException($e, ['exception.escaped' => true])
        ->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
} finally {
    $scope->detach();
    $rootSpan->end();
}
