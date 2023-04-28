<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Exception\ProductPriceCriteriaBuildingFailedException;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;

class ProductPriceCriteriaFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProductPriceCriteriaFactory */
    private $productPriceCriteriaFactory;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->productPriceCriteriaFactory = new ProductPriceCriteriaFactory(
            $this->currencyManager,
            $this->logger
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function testThatProductPriceCriteriaCanBeBuilt()
    {
        $currency = 'USD';

        $result = $this->productPriceCriteriaFactory->build(
            $this->getProduct(1),
            $this->getProductUnit('code'),
            123,
            $currency
        );

        $this->assertInstanceOf(ProductPriceCriteria::class, $result);
        $this->assertEquals($currency, $result->getCurrency());
    }

    public function testThatProductPriceCriteriaCanBeBuiltWithoutUserCurrency()
    {
        $currency = 'EUR';

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $result = $this->productPriceCriteriaFactory->build(
            $this->getProduct(1),
            $this->getProductUnit('code'),
            123
        );

        $this->assertInstanceOf(ProductPriceCriteria::class, $result);
        $this->assertEquals($currency, $result->getCurrency());
    }

    public function testThatProductPriceCriteriaCanNotBeBuiltWithNotValidParams()
    {
        $this->expectException(ProductPriceCriteriaBuildingFailedException::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
                $this->arrayHasKey('message')
            );

        $this->productPriceCriteriaFactory->build(
            $this->getProduct(1),
            $this->getProductUnit('code'),
            -123,
            'USD'
        );
    }

    public function testThatProductPriceCriteriaCanBeBuiltFromProductLineItem()
    {
        $productLineItem = $this->createMock(ProductLineItemInterface::class);

        $productLineItem->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($this->getProduct(1));
        $productLineItem->expects($this->exactly(2))
            ->method('getProductUnit')
            ->willReturn($this->getProductUnit('code'));
        $productLineItem->expects($this->exactly(2))
            ->method('getQuantity')
            ->willReturn(321);

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
        $map = [
            'getProduct' => $this->getProduct(1),
            'getProductUnit' => $this->getProductUnit('code'),
            'getQuantity' => 123
        ];
        unset($map[$nullMethod]);

        $productLineItem = $this->createMock(ProductLineItemInterface::class);
        foreach ($map as $method => $data) {
            $productLineItem->expects($this->any())
                ->method($method)
                ->willReturn($data);
        }

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
                $this->arrayHasKey('message')
            );

        $this->productPriceCriteriaFactory->createListFromProductLineItems(
            [$productLineItem],
            'USD'
        );
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
