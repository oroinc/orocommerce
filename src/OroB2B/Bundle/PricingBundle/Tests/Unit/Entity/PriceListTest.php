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
}
