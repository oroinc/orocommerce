<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Duplicator;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Duplicator\ScheduleDuplicator;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class ScheduleDuplicatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScheduleDuplicator
     */
    protected $scheduleDuplicator;

    protected function setUp()
    {
        $this->scheduleDuplicator = new ScheduleDuplicator();
    }

    public function testDuplicateSchedule()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $sourcePriceList = new PriceList();
        $schedule = new PriceListSchedule();
        $schedule->setDeactivateAt(new \DateTime('+ 1 day', new \DateTimeZone('UTC')));
        $sourcePriceList->addSchedule($schedule);

        $pastSchedule = new PriceListSchedule();
        $pastSchedule->setDeactivateAt(new \DateTime('- 1 day', new \DateTimeZone('UTC')));
        $sourcePriceList->addSchedule($pastSchedule);

        $lastSchedule = new PriceListSchedule();
        $lastSchedule->setActiveAt(new \DateTime('+ 2 day', new \DateTimeZone('UTC')));
        $lastSchedule->setDeactivateAt(null);
        $sourcePriceList->addSchedule($lastSchedule);

        $duplicatedPriceList = new PriceList();
        $this->assertCount(0, $duplicatedPriceList->getSchedules());
        $this->assertFalse($duplicatedPriceList->isContainSchedule());

        $this->scheduleDuplicator->duplicateSchedule($sourcePriceList, $duplicatedPriceList);
        foreach ($duplicatedPriceList->getSchedules() as $schedule) {
            if ($schedule->getDeactivateAt() !== null) {
                $this->assertGreaterThan($now, $schedule->getDeactivateAt());
            }
        }
        $this->assertCount(2, $duplicatedPriceList->getSchedules());
        $this->assertTrue($duplicatedPriceList->isContainSchedule());
    }
}
