<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\EventListener\LineItemValidationListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemValidationListenerTest extends TestCase
{
    private QuantityToOrderValidatorService|MockObject $quantityValidator;

    private LineItemValidationListener $lineItemValidationListener;

    private LineItemValidateEvent|MockObject $event;

    protected function setUp(): void
    {
        $this->quantityValidator = $this->createMock(QuantityToOrderValidatorService::class);
        $this->lineItemValidationListener = new LineItemValidationListener($this->quantityValidator);
        $this->event = $this->createMock(LineItemValidateEvent::class);
    }

    public function testOnLineItemValidateDoesNotValidate(): void
    {
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->event->expects(self::never())
            ->method('addErrorByUnit');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateDoesNotValidateIfNotLineItem(): void
    {
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn(['xxxx']);

        $this->event->expects(self::never())
            ->method('addErrorByUnit');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateForCheckoutLineItem(): void
    {
        $lineItem1 = $this->createMock(CheckoutLineItem::class);
        $lineItem1->expects(self::once())
            ->method('isPriceFixed')
            ->willReturn(false);
        $lineItem2 = $this->createMock(CheckoutLineItem::class);
        $lineItem2->expects(self::once())
            ->method('isPriceFixed')
            ->willReturn(true);
        $lineItem3 = $this->createMock(LineItem::class);

        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $lineItem1->expects(self::once())->method('getProduct');
        $lineItem2->expects(self::never())->method('getProduct');
        $lineItem3->expects(self::once())->method('getProduct');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsMaxErrorToEvent(): void
    {
        $maxMessage = 'maxMessage';
        $lineItem = new LineItem();
        $lineItem->setUnit((new ProductUnit())->setCode('someCode'));
        $product = new ProductStub();
        $product->setSku('someSku');
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn($maxMessage);
        $this->quantityValidator->expects(self::never())
            ->method('getMinimumErrorIfInvalid');

        $this->event->expects(self::once())
            ->method('addErrorByUnit');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsMaxErrorToEventWhenHasChecksum(): void
    {
        $maxMessage = 'maxMessage';
        $lineItem = (new LineItem())
            ->setChecksum('sample_checksum');
        $lineItem->setUnit((new ProductUnit())->setCode('someCode'));
        $product = new ProductStub();
        $product->setSku('someSku');
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn($maxMessage);
        $this->quantityValidator->expects(self::never())
            ->method('getMinimumErrorIfInvalid');

        $this->event->expects(self::once())
            ->method('addErrorByUnit')
            ->with($product->getSku(), $lineItem->getProductUnitCode(), $maxMessage, $lineItem->getChecksum());
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsMinErrorToEvent(): void
    {
        $minMessage = 'minMessage';
        $lineItem = new LineItem();
        $lineItem->setUnit((new ProductUnit())->setCode('someCode'));
        $product = new ProductStub();
        $product->setSku('someSku');
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn(false);
        $this->quantityValidator->expects(self::once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn($minMessage);

        $this->event->expects(self::once())
            ->method('addErrorByUnit');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsMinErrorToEventWhenHasChecksum(): void
    {
        $minMessage = 'minMessage';
        $lineItem = (new LineItem())
            ->setChecksum('sample_checksum');
        $lineItem->setUnit((new ProductUnit())->setCode('someCode'));
        $product = new ProductStub();
        $product->setSku('someSku');
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn(false);
        $this->quantityValidator->expects(self::once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn($minMessage);

        $this->event->expects(self::once())
            ->method('addErrorByUnit')
            ->with($product->getSku(), $lineItem->getProductUnitCode(), $minMessage, $lineItem->getChecksum());
        ;
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }
}
