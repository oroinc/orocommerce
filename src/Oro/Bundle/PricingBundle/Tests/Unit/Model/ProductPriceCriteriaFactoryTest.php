<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Exception\ProductPriceCriteriaBuildingFailedException;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ProductPriceCriteriaFactoryTest extends \PHPUnit\Framework\TestCase
{
    private LoggerInterface $logger;

    private UserCurrencyManager $currencyManager;

    private ProductPriceCriteriaFactory $productPriceCriteriaFactory;

    private Product $product;

    private ProductUnit $productUnit;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->product = $this->createMock(Product::class);
        $this->productUnit = $this->createMock(ProductUnit::class);

        $this->product->method('getId')->willReturn(1);
        $this->productUnit->method('getCode')->willReturn('code');

        $this->productPriceCriteriaFactory = new ProductPriceCriteriaFactory(
            $this->logger,
            $this->currencyManager
        );
    }

    public function testThatProductPriceCriteriaCanBeBuilt()
    {
        $currency = 'USD';

        $result = $this->productPriceCriteriaFactory->build(
            $this->product,
            $this->productUnit,
            123,
            $currency
        );

        $this->assertInstanceOf(ProductPriceCriteria::class, $result);
        $this->assertEquals($currency, $result->getCurrency());
    }

    public function testThatProductPriceCriteriaCanBeBuiltWithoutUserCurrency()
    {
        $currency = 'EUR';

        $this->currencyManager->expects($this->once())->method('getUserCurrency')->willReturn($currency);

        $result = $this->productPriceCriteriaFactory->build(
            $this->product,
            $this->productUnit,
            123
        );

        $this->assertInstanceOf(ProductPriceCriteria::class, $result);
        $this->assertEquals($currency, $result->getCurrency());
    }

    public function testThatProductPriceCriteriaCanNotBeBuiltWithNotValidParams()
    {
        $this->expectException(ProductPriceCriteriaBuildingFailedException::class);

        $this->logger->expects($this->once())->method('error')->with(
            $this->equalTo('Got error while trying to create new ProductPriceCriteria with message: "{message}"'),
            $this->arrayHasKey('message')
        );

        $this->productPriceCriteriaFactory->build(
            $this->product,
            $this->productUnit,
            -123,
            'USD'
        );
    }

    public function testThatProductPriceCriteriaCanBeBuiltFromProductLineItem()
    {
        $productLineItem = $this->createMock(ProductLineItemInterface::class);

        $productLineItem->expects($this->exactly(2))->method('getProduct')->willReturn($this->product);
        $productLineItem->expects($this->exactly(2))->method('getProductUnit')->willReturn($this->productUnit);
        $productLineItem->expects($this->exactly(2))->method('getQuantity')->willReturn(321);

        $result= $this->productPriceCriteriaFactory->createListFromProductLineItems(
            [$productLineItem],
            'USD'
        );

        foreach ($result as $item) {
            $this->assertInstanceOf(ProductPriceCriteria::class, $item);
        }
    }

    /**
     * @dataProvider provideProductLineItemNullMethod
     */
    public function testThatProductPriceCriteriaCanNotBeBuiltWhenArgumentIsNull(string $nullMethod)
    {
        $productLineItem = $this->prepareProductLineItem($nullMethod);

        $this->logger->expects($this->once())->method('error')->with(
            $this->equalTo('Got error while trying to create new ProductPriceCriteria with message: "{message}"'),
            $this->arrayHasKey('message')
        );

        $this->productPriceCriteriaFactory->createListFromProductLineItems(
            [$productLineItem],
            'USD'
        );
    }

    private function prepareProductLineItem(string $nullMethod): ProductLineItemInterface|MockObject
    {
        $item = $this->createMock(ProductLineItemInterface::class);

        $map = [
            'getProduct' => $this->product,
            'getProductUnit' => $this->productUnit,
            'getQuantity' => 123
        ];

        unset($map[$nullMethod]);

        foreach ($map as $method => $data) {
            $item->method($method)->willReturn($data);
        }

        return $item;
    }

    private function provideProductLineItemNullMethod(): array
    {
        return [
            ['getProduct'],
            ['getQuantity'],
            ['getProductUnit']
        ];
    }
}
