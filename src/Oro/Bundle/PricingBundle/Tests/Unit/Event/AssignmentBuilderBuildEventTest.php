<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class AssignmentBuilderBuildEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var AssignmentBuilderBuildEvent
     */
    protected $assignmentBuilderBuildEvent;

    protected function setUp()
    {
        $this->assignmentBuilderBuildEvent = new AssignmentBuilderBuildEvent();
    }

    public function testSetPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);

        $this->assignmentBuilderBuildEvent->setPriceList($priceList);
        $this->assertEquals($priceList, $this->assignmentBuilderBuildEvent->getPriceList());
    }
}
