<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\OrderLineItemIsPriceOverriddenCalculator;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemIsPriceOverriddenCalculatorTest extends TestCase
{
    private ProductPriceByMatchingCriteriaProvider&MockObject $priceByMatchingCriteriaProvider;
    private OrderLineItemIsPriceOverriddenCalculator $calculator;

    #[\Override]
    protected function setUp(): void
    {
        $this->priceByMatchingCriteriaProvider = $this->createMock(ProductPriceByMatchingCriteriaProvider::class);
        $this->calculator = new OrderLineItemIsPriceOverriddenCalculator($this->priceByMatchingCriteriaProvider);
    }

    public function testReturnsFalseWhenTierPricesEmpty(): void
    {
        $this->priceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method('getProductPriceMatchingCriteria');

        $lineItem = $this->createLineItem(price: Price::create(10.0, 'USD'));

        self::assertFalse($this->calculator->isOverridden($lineItem, []));
    }

    /**
     * @dataProvider incompleteLineItemProvider
     */
    public function testReturnsFalseWhenRequiredLineItemFieldIsMissing(OrderLineItem $lineItem): void
    {
        $this->priceByMatchingCriteriaProvider
            ->expects(self::never())
            ->method('getProductPriceMatchingCriteria');

        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 10.0, 'USD');

        self::assertFalse($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    /**
     * @return iterable<string, array{OrderLineItem}>
     */
    public static function incompleteLineItemProvider(): iterable
    {
        $unit = (new ProductUnit())->setCode('item');
        $product = new ProductStub();
        $product->setId(1);

        $withoutProduct = new OrderLineItem();
        $withoutProduct->setProductUnit($unit);
        $withoutProduct->setPrice(Price::create(10.0, 'USD'));
        yield 'no product' => [$withoutProduct];

        $withoutUnit = new OrderLineItem();
        $withoutUnit->setProduct($product);
        $withoutUnit->setPrice(Price::create(10.0, 'USD'));
        yield 'no product unit' => [$withoutUnit];

        $withoutPrice = new OrderLineItem();
        $withoutPrice->setProduct($product);
        $withoutPrice->setProductUnit($unit);
        yield 'no price' => [$withoutPrice];
    }

    public function testReturnsFalseWhenNoMatchingTierPriceFound(): void
    {
        $lineItem = $this->createLineItem(price: Price::create(9.5, 'USD'), quantity: 5.0);
        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 10.0, 'USD');

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->willReturn(null);

        self::assertFalse($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    public function testReturnsFalseWhenPriceMatchesMatchedTierPriceExactly(): void
    {
        $lineItem = $this->createLineItem(price: Price::create(10.0, 'USD'));
        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 10.0, 'USD');

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->willReturn($tierPrice);

        self::assertFalse($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    public function testReturnsFalseWhenPriceDifferenceIsWithinEpsilon(): void
    {
        $lineItem = $this->createLineItem(price: Price::create(10.0000005, 'USD'));
        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 10.0, 'USD');

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->willReturn($tierPrice);

        self::assertFalse($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    public function testReturnsTrueWhenPriceDiffersFromMatchedTierPrice(): void
    {
        $lineItem = $this->createLineItem(price: Price::create(7.5, 'USD'), quantity: 5.0);
        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 8.0, 'USD');

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with(
                self::callback(static function (ProductPriceCriteria $criteria) {
                    return $criteria->getQuantity() === 5.0
                        && $criteria->getCurrency() === 'USD'
                        && $criteria->getProductUnit()->getCode() === 'item';
                }),
                self::isInstanceOf(ProductPriceCollectionDTO::class)
            )
            ->willReturn($tierPrice);

        self::assertTrue($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    public function testUsesCurrencyFromOrderWhenPriceCurrencyDiffers(): void
    {
        // Simulates currency change (USD → EUR) before saving:
        // line item price still has USD, but order currency is already EUR.
        $order = new Order();
        $order->setCurrency('EUR');

        $lineItem = $this->createLineItem(price: Price::create(10.0, 'USD'), quantity: 1.0);
        $lineItem->addOrder($order);

        $tierPrice = $this->createTierPrice(1, 'item', 1.0, 10.0, 'EUR');

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with(
                self::callback(static function (ProductPriceCriteria $criteria) {
                    // Criteria must use the order's EUR currency, not the price's USD.
                    return $criteria->getCurrency() === 'EUR';
                }),
                self::isInstanceOf(ProductPriceCollectionDTO::class)
            )
            ->willReturn($tierPrice);

        // 10.0 USD vs 10.0 EUR — same value, different currency, but criteria matched → not overridden.
        self::assertFalse($this->calculator->isOverridden($lineItem, [$tierPrice]));
    }

    private function createLineItem(
        ?Price $price,
        float $quantity = 1.0,
        string $unitCode = 'item',
        int $productId = 1,
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($this->createProduct($productId));
        $lineItem->setProductUnit($this->createUnit($unitCode));
        $lineItem->setQuantity($quantity);
        if ($price !== null) {
            $lineItem->setPrice($price);
        }

        return $lineItem;
    }

    private function createProduct(int $id): ProductStub
    {
        $product = new ProductStub();
        $product->setId($id);

        return $product;
    }

    private function createUnit(string $code): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }

    private function createTierPrice(
        int $productId,
        string $unitCode,
        float $quantity,
        float $value,
        string $currency,
    ): ProductPriceDTO {
        return new ProductPriceDTO(
            $this->createProduct($productId),
            Price::create($value, $currency),
            $quantity,
            $this->createUnit($unitCode),
        );
    }
}
