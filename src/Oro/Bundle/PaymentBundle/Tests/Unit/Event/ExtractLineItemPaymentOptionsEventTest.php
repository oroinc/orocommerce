<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractLineItemPaymentOptionsEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtractLineItemPaymentOptionsEvent */
    protected $event;

    /** @var LineItemsAwareInterface|\PHPUnit_Framework_MockObject_MockObject $entityMock */
    protected $entityMock;

    protected function setUp()
    {
        $this->entityMock = $this->getMock(LineItemsAwareInterface::class);
        $this->event = new ExtractLineItemPaymentOptionsEvent($this->entityMock);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entityMock, $this->event->getEntity());
    }

    public function testGetModels()
    {
        $this->event->addModel(new LineItemOptionModel());

        $this->assertInternalType('array', $this->event->getModels());
        $this->assertContainsOnlyInstancesOf(LineItemOptionModel::class, $this->event->getModels());
    }

    public function testGetModelsWithCount()
    {
        $model = new LineItemOptionModel();
        $this->event->addModel($model);

        $this->assertInternalType('array', $this->event->getModels());
        $this->assertCount(1, $this->event->getModels());

        $this->event->addModel($model);
        $this->assertCount(1, $this->event->getModels());
    }

    public function testGetModelsEmpty()
    {
        $this->assertInternalType('array', $this->event->getModels());
        $this->assertEmpty($this->event->getModels());
    }
}
