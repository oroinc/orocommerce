<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Entity\PriceRule;

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
                ['productAssignmentRule', 'test rule'],
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

    public function testAddPriceRule()
    {
        $priceList = $this->createPriceList();
        $priceRule = new PriceRule();

        $priceList->addPriceRule($priceRule);
        $this->assertSame($priceRule->getPriceList(), $priceList);
        $this->assertSame($priceList->getPriceRules()->first(), $priceRule);
    }

    public function testSetPriceRules()
    {
        $priceList = $this->createPriceList();
        $priceRule1 = new PriceRule();
        $priceRule2 = new PriceRule();

        $priceList->setPriceRules(new ArrayCollection([$priceRule1, $priceRule2]));

        $this->assertCount(2, $priceList->getPriceRules());
    }

    public function testRemovePriceRule()
    {
        $priceList = $this->createPriceList();
        $priceRule1 = new PriceRule();
        $priceRule2 = new PriceRule();

        $priceList->setPriceRules(new ArrayCollection([$priceRule1, $priceRule2]));

        $priceList->removePriceRule($priceRule1);
        $this->assertCount(1, $priceList->getPriceRules());
        $this->assertSame($priceList->getPriceRules()->first(), $priceRule2);
    }
}
