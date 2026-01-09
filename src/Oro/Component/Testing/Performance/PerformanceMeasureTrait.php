<?php

namespace Oro\Component\Testing\Performance;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Provides performance measurement capabilities for test classes.
 *
 * This trait offers methods to start and stop performance measurements using Symfony's Stopwatch,
 * allowing test classes to track execution time of specific operations. Measurements are identified
 * by name and can be retrieved to verify performance characteristics.
 */
trait PerformanceMeasureTrait
{
    /** @var Stopwatch */
    protected static $stopwatch;

    /**
     * @param string $testName
     */
    public static function startMeasurement($testName)
    {
        self::initializeStopwatch();
        self::$stopwatch->start($testName);
    }

    /**
     * @param string $testName
     * @return int
     */
    public static function stopMeasurement($testName)
    {
        self::initializeStopwatch();
        self::$stopwatch->stop($testName);

        return self::$stopwatch->getEvent($testName)->getDuration();
    }

    private static function initializeStopwatch()
    {
        if (!self::$stopwatch) {
            self::$stopwatch = new Stopwatch();
        }
    }
}
