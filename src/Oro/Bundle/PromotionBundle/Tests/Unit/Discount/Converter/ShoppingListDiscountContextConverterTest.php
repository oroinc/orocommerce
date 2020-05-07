<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\ShoppingListDiscountContextConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListDiscountContextConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var LineItemsToDiscountLineItemsConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemsConverter;

    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyManager;

    /**
     * @var LineItemNotPricedSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemNotPricedSubtotalProvider;

    /**
     * @var ShoppingListDiscountContextConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->lineItemsConverter = $this->createMock(LineItemsToDiscountLineItemsConverter::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->lineItemNotPricedSubtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);

        $this->converter = new ShoppingListDiscountContextConverter(
            $this->lineItemsConverter,
            $this->currencyManager,
            $this->lineItemNotPricedSubtotalProvider
        );
    }

    public function testConvert(): void
    {
        $sourceEntity = new ShoppingList();
        $amount = 100;
        $currency = 'USD';

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 42]);
        $sourceEntity->addLineItem($lineItem);

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $this->lineItemNotPricedSubtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($sourceEntity, $currency)
            ->willReturn((new Subtotal())->setAmount($amount)->setCurrency($currency));

        $discountLineItems = [
            (new DiscountLineItem())->setSubtotal($amount)
        ];

        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with([$lineItem])
            ->willReturn($discountLineItems);

        $expectedDiscountContext = new DiscountContext();
        $expectedDiscountContext->setSubtotal(100);
        $expectedDiscountContext->setLineItems($discountLineItems);

        $this->assertEquals($expectedDiscountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertWithCalculatedSubtotal(): void
    {
        $amount = 100;

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 42]);

        $sourceEntity = new ShoppingList();
        $sourceEntity->addLineItem($lineItem);
        $sourceEntity->setSubtotal((new Subtotal())->setAmount($amount));

        $this->currencyManager->expects($this->never())
            ->method('getUserCurrency');

        $this->lineItemNotPricedSubtotalProvider->expects($this->never())
            ->method('getSubtotalByCurrency');

        $discountLineItems = [
            (new DiscountLineItem())->setSubtotal($amount)
        ];

        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with([$lineItem])
            ->willReturn($discountLineItems);

        $expectedDiscountContext = new DiscountContext();
        $expectedDiscountContext->setSubtotal($amount);
        $expectedDiscountContext->setLineItems($discountLineItems);

        $this->assertEquals($expectedDiscountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertUnsupportedException(): void
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->convert($entity);
    }

    /**
     * @dataProvider supportsDataProvider
     * @param object $entity
     * @param bool $isSupported
     */
    public function testSupports(object $entity, bool $isSupported): void
    {
        $this->assertSame($isSupported, $this->converter->supports($entity));
    }

    /**
     * @return array
     */
    public function supportsDataProvider(): array
    {
        return [
            'supported entity' => [
                'entity' => new ShoppingList(),
                'isSupported' => true
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
        ];
    }
}
