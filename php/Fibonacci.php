<?php

declare(strict_types=1);

namespace FibonacciService;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\Context;

class Fibonacci
{
    public function __construct(private TracerInterface $tracer)
    {
    }

    public function calculate(int $n): int
    {
        $span = $this->tracer->spanBuilder("Fibonacci($n)")
            ->startSpan();
        $scope = $span->activate();

        try {
            $result = $this->fibonacciRecursive($n);
            $span->setAttribute('n', $n);
            $span->setAttribute('result', $result);
            return $result;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    private function fibonacciRecursive(int $n): int
    {
        $span = $this->tracer->spanBuilder("fibonacciRecursive($n)")->startSpan();
        $scope = $span->activate();

        try {
            if ($n <= 1) {
                $result = 1;
            } else {
                $a = $this->fibonacciRecursive($n - 1);
                $b = $this->fibonacciRecursive($n - 2);
                $result = $a + $b;
            }
            $span->setAttribute('n', $n);
            $span->setAttribute('result', $result);
            return $result;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}