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
        $sourcePriceList = new PriceList();
        $schedule = new PriceListSchedule();
        $schedule->setDeactivateAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC')));
        $pastSchedule = clone  $schedule;
        $pastSchedule->setDeactivateAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')));
        $sourcePriceList->addSchedule($schedule);
        $sourcePriceList->addSchedule($pastSchedule);

        $duplicatedPriceList = new PriceList();
        $this->assertCount(0, $duplicatedPriceList->getSchedules());
        $this->assertFalse($duplicatedPriceList->isContainSchedule());
        $this->scheduleDuplicator->duplicateSchedule($sourcePriceList, $duplicatedPriceList);
        $this->assertCount(1, $duplicatedPriceList->getSchedules());
        $this->assertTrue($duplicatedPriceList->isContainSchedule());
    }
}
