<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\InventoryBundle\EventListener\LineItemValidationListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LineItemValidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quantityValidator;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var LineItemValidationListener
     */
    protected $lineItemValidationListener;

    /**
     * @var LineItemValidateEvent|\PHPUnit_Framework_MockObject_MockObject $event *
     */
    protected $event;

    protected function setUp()
    {
        $this->quantityValidator = $this->getMockBuilder(QuantityToOrderValidatorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->lineItemValidationListener = new LineItemValidationListener($this->quantityValidator, $this->translator);
        $this->event = $this->getMockBuilder(LineItemValidateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnLineItemValidateDoesNotValidate()
    {
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->event->expects($this->never())
            ->method('addError');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateDoesNotValidateIfNotLineItem()
    {
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn(['xxxx']);

        $this->event->expects($this->never())
            ->method('addError');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsErrorToEvent()
    {
        $lineItem = new LineItem();
        $product = new ProductStub();
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects($this->once())
            ->method('isHigherThanMaxLimit')
            ->willReturn(true);
        $this->quantityValidator->expects($this->once())
            ->method('isLowerThenMinLimit')
            ->willReturn(true);

        $this->event->expects($this->exactly(2))
            ->method('addError');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }
}
