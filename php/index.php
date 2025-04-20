<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use FibonacciService\Fibonacci;

$tracer = setupOpenTelemetry();

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

try {
    $fibonacci = new Fibonacci($tracer);
    $result = $fibonacci->calculate($n);

    header('Content-Type: text/plain');
    echo $result . "\n";

} catch (\Throwable $e) {
    http_response_code(500);
    echo "Error: {$e->getMessage()}";
}