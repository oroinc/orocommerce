<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Performance;

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
        self::verifyStopwatchInstance();
        self::$stopwatch->start($testName);
    }

    /**
     * @param string $testName
     * @return int
     */
    public static function stopMeasurement($testName)
    {
        self::verifyStopwatchInstance();
        self::$stopwatch->stop($testName);

        return self::$stopwatch->getEvent($testName)->getDuration();
    }

    private static function verifyStopwatchInstance()
    {
        if (!self::$stopwatch) {
            self::$stopwatch = new Stopwatch();
        }
    }
}
