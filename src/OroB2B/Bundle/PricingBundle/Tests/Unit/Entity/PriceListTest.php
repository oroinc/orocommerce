<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->createPriceList(),
            [
                ['default', false],
                ['active', true],
            ]
        );
    }

    /**
     * @return PriceList
     */
    protected function createPriceList()
    {
        return new PriceList();
    }

    public function testAddSchedule()
    {
        $priceList = $this->createPriceList();
        $schedule = new PriceListSchedule();

        $priceList->addSchedule($schedule);
        $this->assertSame($schedule->getPriceList(), $priceList);
        $this->assertSame($priceList->getSchedules()->first(), $schedule);
    }

    public function testRemoveSchedule()
    {
        $priceList = $this->createPriceList();
        $schedule = new PriceListSchedule();
        $schedule2 = new PriceListSchedule();

        $priceList->setSchedules(new ArrayCollection([$schedule, $schedule2]));

        $priceList->removeSchedule($schedule);
        $this->assertCount(1, $priceList->getSchedules());
        $this->assertSame($priceList->getSchedules()->first(), $schedule2);
    }

    public function testHasSchedule()
    {
        $date1 = '2016-03-01T22:00:00Z';
        $date2 = '2016-04-01T22:00:00Z';
        $date3 = '2016-05-01T22:00:00Z';

        $priceList = new PriceList();
        $priceList
            ->addSchedule(new PriceListSchedule(new \DateTime($date1), new \DateTime($date2)))
            ->addSchedule(new PriceListSchedule());

        $needle = new PriceListSchedule(
            new \DateTime($date1),
            new \DateTime($date2)
        );
        $needle->setPriceList($priceList);

        $this->assertTrue($priceList->hasSchedule($needle));
        $this->assertFalse($priceList->hasSchedule(new PriceListSchedule(
            new \DateTime($date1),
            new \DateTime($date3)
        )));
    }
}
