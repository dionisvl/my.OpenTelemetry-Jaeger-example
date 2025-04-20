<?php

declare(strict_types=1);

namespace FibonacciService;

use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;

class Fibonacci
{
    public function __construct(private TracerInterface $tracer)
    {
    }

    /**
     * @throws \Throwable
     */
    public function calculate(int $n): int
    {
        if ($n < 0) {
            throw new \InvalidArgumentException("Input n must be non-negative.");
        }

        return $this->calculateRecursive($n);
    }

    private function calculateRecursive(int $n): int
    {
        $span = $this->tracer->spanBuilder("Fibonacci::recursive(n=$n)")
            ->startSpan();
        $scope = $span->activate();

        try {
            if ($n <= 1) {
                $result = 1;
            } else {
                $result = $this->calculateRecursive($n - 1) + $this->calculateRecursive($n - 2);
            }

            $span->setAttribute('parameter.n', $n);
            $span->setAttribute('result', $result);
            return $result;

        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}