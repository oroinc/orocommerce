<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalAmountProvider;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\Converter\CheckoutDiscountContextConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutDiscountContextConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderLineItemsToDiscountLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsConverter;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutLineItemsManager;

    /**
     * @var CheckoutSubtotalAmountProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSubtotalAmountProvider;

    /**
     * @var CheckoutDiscountContextConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->lineItemsConverter = $this->createMock(OrderLineItemsToDiscountLineItemsConverter::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutSubtotalAmountProvider = $this->createMock(CheckoutSubtotalAmountProvider::class);
        $this->converter = new CheckoutDiscountContextConverter(
            $this->lineItemsConverter,
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalAmountProvider
        );
    }

    public function testConvert()
    {
        $sourceEntity = $this->getCheckout();
        $subtotal = 100.0;
        $lineItems = [new OrderLineItem()];
        $discountLineItems = [new DiscountLineItem()];
        $this->checkoutSubtotalAmountProvider->expects($this->once())
            ->method('getSubtotalAmount')
            ->with($sourceEntity)
            ->willReturn($subtotal);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($sourceEntity)
            ->willReturn(new ArrayCollection($lineItems));
        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with($lineItems)
            ->willReturn($discountLineItems);

        $expectedDiscountContext = new DiscountContext();
        $expectedDiscountContext->setSubtotal($subtotal);
        $expectedDiscountContext->setLineItems($discountLineItems);

        $this->assertEquals($expectedDiscountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertUnsupportedException()
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->convert($entity);
    }

    /**
     * @dataProvider supportsDataProvider
     * @param object $entity
     * @param boolean $isSupported
     */
    public function testSupports($entity, $isSupported)
    {
        $this->assertSame($isSupported, $this->converter->supports($entity));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'supported entity' => [
                'entity' => $this->getCheckout(),
                'isSupported' => true
            ],
            'supported entity but with not supported source entity' => [
                'entity' => $this->getCheckout(\stdClass::class),
                'isSupported' => false
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
        ];
    }

    /**
     * @param string $sourceEntityClass
     * @return Checkout
     */
    private function getCheckout($sourceEntityClass = ShoppingList::class)
    {
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $source */
        $source = $this->createMock(CheckoutSource::class);
        $source->expects($this->any())
            ->method('getEntity')
            ->willReturn(new $sourceEntityClass);
        $checkout = new Checkout();
        $checkout->setSource($source);

        return $checkout;
    }
}
