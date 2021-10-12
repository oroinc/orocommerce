<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderDiscountContextConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\OrderStub;
use Oro\Bundle\SaleBundle\Entity\Quote;

class OrderDiscountContextConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderLineItemsToDiscountLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsConverter;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsSubtotalProvider;

    /** @var OrderDiscountContextConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->lineItemsConverter = $this->createMock(OrderLineItemsToDiscountLineItemsConverter::class);
        $this->lineItemsSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);

        $this->converter = new OrderDiscountContextConverter(
            $this->lineItemsConverter,
            $this->lineItemsSubtotalProvider
        );
    }

    public function testConvert()
    {
        $sourceEntity = new OrderStub();
        $sourceEntity->setEstimatedShippingCostAmount(20);
        $sourceEntity->setCurrency('USD');
        $subtotalAmount = 100.0;
        $lineItems = [new OrderLineItem()];
        $sourceEntity->setLineItems(new ArrayCollection($lineItems));
        $discountLineItems = [new DiscountLineItem()];
        $subtotal = new Subtotal();
        $subtotal->setAmount($subtotalAmount);

        $this->lineItemsSubtotalProvider->expects($this->once())
            ->method('getSubtotal')
            ->with($sourceEntity)
            ->willReturn($subtotal);
        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with($lineItems)
            ->willReturn($discountLineItems);

        $expectedDiscountContext = new DiscountContext();
        $expectedDiscountContext->setSubtotal($subtotalAmount);
        $expectedDiscountContext->setLineItems($discountLineItems);
        $expectedDiscountContext->setShippingCost(20);

        $this->assertEquals($expectedDiscountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertUnsupportedException()
    {
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->convert(new \stdClass());
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(object $entity, bool $isSupported)
    {
        $this->assertSame($isSupported, $this->converter->supports($entity));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported entity' => [
                'entity' => new OrderStub(),
                'isSupported' => true
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
            'not supported order' => [
                'entity' => (new OrderStub())->setSourceEntityClass(Quote::class),
                'isSupported' => false
            ],
            'order with disabled promotions' => [
                'entity' => (new OrderStub())->setDisablePromotions(true),
                'isSupported' => false
            ],
            'order with disabled promotions and not supported order' => [
                'entity' => (new OrderStub())->setDisablePromotions(true)->setSourceEntityClass(Quote::class),
                'isSupported' => false
            ],
        ];
    }
}
