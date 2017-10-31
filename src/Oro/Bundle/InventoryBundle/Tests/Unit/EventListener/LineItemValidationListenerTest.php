<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\EventListener\LineItemValidationListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LineItemValidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quantityValidator;

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
        $this->lineItemValidationListener = new LineItemValidationListener($this->quantityValidator);
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

    /**
     * @dataProvider sourceEntityDataProvider
     *
     * @param string $sourceEntityClass
     * @param bool $expected
     */
    public function testOnLineItemValidateForCheckoutLineItem($sourceEntityClass, $expected)
    {
        $sourceEntity = $this->createMock($sourceEntityClass);
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);
        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem]));

        $lineItem->expects($this->exactly((int) $expected))
            ->method('getProduct');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    /**
     * @return array
     */
    public function sourceEntityDataProvider()
    {
        return [
            'quoteDemand' => [
                'sourceEntityClass' => QuoteDemand::class,
                'expected' => false,
            ],
            'shoppingList' => [
                'sourceEntityClass' => ShoppingList::class,
                'expected' => true,
            ],
            'some other source entity' => [
                'sourceEntityClass' => Checkout::class,
                'expected' => false,
            ],
        ];
    }

    public function testOnLineItemValidateAddsMaxErrorToEvent()
    {
        $maxMessage = 'maxMessage';
        $lineItem = new LineItem();
        $product = new ProductStub();
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn($maxMessage);
        $this->quantityValidator->expects($this->never())
            ->method('getMinimumErrorIfInvalid');

        $this->event->expects($this->once())
            ->method('addError');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidateAddsMinErrorToEvent()
    {
        $minMessage = 'minMessage';
        $lineItem = new LineItem();
        $product = new ProductStub();
        $lineItem->setProduct($product);
        $lineItems = new ArrayCollection();
        $lineItems->add($lineItem);
        $this->event->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->quantityValidator->expects($this->once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn(false);
        $this->quantityValidator->expects($this->once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn($minMessage);

        $this->event->expects($this->once())
            ->method('addError');
        $this->lineItemValidationListener->onLineItemValidate($this->event);
    }
}
