<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductPriceCriteriaBuilder;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\SimpleProductPriceCriteriaBuilder;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SimpleProductPriceCriteriaBuilderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private SimpleProductPriceCriteriaBuilder $builder;

    private EntityManager|MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->builder = new SimpleProductPriceCriteriaBuilder($managerRegistry, $this->userCurrencyManager);

        $this->setUpLoggerMock($this->builder);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->with(ProductUnit::class)
            ->willReturn($this->entityManager);
    }

    public function testCreateWhenNothingProvided(): void
    {
        $throwable = new \InvalidArgumentException('Product must have id.');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to create a product price criteria for: product #{product_id},'
                . 'unit "{unit_code}", quantity "{quantity}", currency "{currency}". Error: {message}',
                [
                    'throwable' => $throwable,
                    'message' => $throwable->getMessage(),
                    'product_id' => null,
                    'unit_code' => null,
                    'quantity' => null,
                    'currency' => null,
                ]
            );

        self::assertNull($this->builder->create());
    }

    public function testCreateWhenProductProvided(): void
    {
        $product = (new ProductStub())->setId(10);

        $this->builder->setProduct($product);

        $throwable = new \InvalidArgumentException('ProductUnit must have code.');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to create a product price criteria for: product #{product_id},'
                . 'unit "{unit_code}", quantity "{quantity}", currency "{currency}". Error: {message}',
                [
                    'throwable' => $throwable,
                    'message' => $throwable->getMessage(),
                    'product_id' => $product->getId(),
                    'unit_code' => null,
                    'quantity' => null,
                    'currency' => null,
                ]
            );

        self::assertNull($this->builder->create());
    }

    public function testCreateWhenProductUnitProvided(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $quantity = 0.0;

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity);

        $throwable = new \InvalidArgumentException('Currency must be non-empty string.');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to create a product price criteria for: product #{product_id},'
                . 'unit "{unit_code}", quantity "{quantity}", currency "{currency}". Error: {message}',
                [
                    'throwable' => $throwable,
                    'message' => $throwable->getMessage(),
                    'product_id' => $product->getId(),
                    'unit_code' => $productUnitItem->getCode(),
                    'quantity' => $quantity,
                    'currency' => null,
                ]
            );

        self::assertNull($this->builder->create());
    }

    public function testCreateWhenProductUnitCodeProvided(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitEachCode = 'each';
        $productUnitEach = (new ProductUnit())->setCode($productUnitEachCode);
        $currency = 'USD';
        $quantity = 0.0;

        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(ProductUnit::class, $productUnitEachCode)
            ->willReturn($productUnitEach);

        $this->builder
            ->setProduct($product)
            ->setProductUnitCode($productUnitEachCode)
            ->setCurrency($currency)
            ->setQuantity($quantity);

        $this->assertLoggerNotCalled();

        self::assertEquals(
            new ProductPriceCriteria(
                $product,
                $productUnitEach,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }

    public function testCreateWhenCurrencyProvided(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $currency = 'USD';
        $quantity = 0.0;

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setCurrency($currency)
            ->setQuantity($quantity);

        $this->assertLoggerNotCalled();

        self::assertEquals(
            new ProductPriceCriteria(
                $product,
                $productUnitItem,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }

    public function testCreateWhenCurrencyFallbackToUser(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $currency = 'EUR';
        $quantity = 0.0;

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity);

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $this->assertLoggerNotCalled();
        self::assertEquals(
            new ProductPriceCriteria(
                $product,
                $productUnitItem,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }

    public function testCreateWhenCurrencyFallbackToDefault(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $currency = 'CAD';
        $quantity = 0.0;

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity);

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn(null);

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn($currency);

        $this->assertLoggerNotCalled();
        self::assertEquals(
            new ProductPriceCriteria(
                $product,
                $productUnitItem,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }

    public function testCreateWhenQuantityProvided(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $quantity = 12.3456;
        $currency = 'USD';

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity)
            ->setCurrency($currency);

        $this->assertLoggerNotCalled();

        self::assertEquals(
            new ProductPriceCriteria(
                $product,
                $productUnitItem,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }
}
