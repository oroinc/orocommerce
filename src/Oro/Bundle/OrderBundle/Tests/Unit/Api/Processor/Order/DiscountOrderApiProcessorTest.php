<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Api\Processor\Order\DiscountOrderApiProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;

class DiscountOrderApiProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventSubscriberInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventSubscriber;

    /**
     * @var DiscountOrderApiProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $this->testedProcessor = new DiscountOrderApiProcessor($this->eventSubscriber);
    }

    /**
     * @return FormContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock()
    {
        return $this->createMock(FormContext::class);
    }

    /**
     * @return FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFormBuilderMock()
    {
        return $this->createMock(FormBuilderInterface::class);
    }

    public function testSuccessfulProcess()
    {
        $contextMock = $this->createContextMock();
        $formBuilderMock = $this->createFormBuilderMock();

        $contextMock
            ->expects(static::once())
            ->method('getFormBuilder')
            ->willReturn($formBuilderMock);

        $formBuilderMock
            ->expects(static::once())
            ->method('addEventSubscriber')
            ->with($this->eventSubscriber);

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongContext()
    {
        $contextMock = $this->createMock(ContextInterface::class);

        $contextMock
            ->expects(static::never())
            ->method(static::anything());

        $this->testedProcessor->process($contextMock);
    }
}
