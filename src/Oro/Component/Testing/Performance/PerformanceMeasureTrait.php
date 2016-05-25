<?php

namespace Oro\Component\Testing\Performance;

use Symfony\Component\Stopwatch\Stopwatch;

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
