<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\AffectedPriceListsHandler;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Entity\EntityListener\AssignmentRuleListener;

class AssignmentRuleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AffectedPriceListsHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $affectedPriceListsHandler;

    /**
     * @var AssignmentRuleListener
     */
    protected $assignmentRuleListener;

    protected function setUp()
    {
        $this->affectedPriceListsHandler = $this->getMockBuilder(AffectedPriceListsHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assignmentRuleListener = new AssignmentRuleListener($this->affectedPriceListsHandler);
    }

    public function testOnAssignmentRuleBuilderBuild()
    {
        $priceList = new PriceList();

        $event = new AssignmentBuilderBuildEvent();
        $event->setPriceList($priceList);

        $this->affectedPriceListsHandler->expects($this->once())
            ->method('recalculateByPriceList')
            ->with(
                $priceList,
                AffectedPriceListsHandler::FIELD_ASSIGNED_PRODUCTS,
                false
            );

        $this->assignmentRuleListener->onAssignmentRuleBuilderBuild($event);
    }
}
