<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\PricingBundle\Model\PriceListHandler;

class PriceListHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListChangeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject $triggerHandler
     */
    protected $triggerHandler;

    /**
     * @var PriceListHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->triggerHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new PriceListHandler($this->triggerHandler);
    }

    public function testHandle()
    {
        $this->triggerHandler->expects($this->once())
            ->method('handleFullRebuild');

        $this->handler->handleDelete();
    }
}
