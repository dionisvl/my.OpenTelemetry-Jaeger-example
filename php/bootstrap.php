<?php
declare(strict_types=1);

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessorBuilder;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Trace\TracerProvider;


function setupOpenTelemetry(): TracerInterface {
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

    return $tracerProvider->getTracer($serviceName, '1.0.0');
}