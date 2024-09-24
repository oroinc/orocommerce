<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\Builder;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilder;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitPriceCriteriaBuilderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private ProductKitPriceCriteriaBuilder $builder;

    private EntityManager|MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->builder = new ProductKitPriceCriteriaBuilder($managerRegistry, $this->userCurrencyManager);

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
            new ProductKitPriceCriteria(
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
            new ProductKitPriceCriteria(
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
            new ProductKitPriceCriteria(
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
            new ProductKitPriceCriteria(
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
            new ProductKitPriceCriteria(
                $product,
                $productUnitItem,
                $quantity,
                $currency
            ),
            $this->builder->create()
        );
    }

    public function testCreateWhenKitItemsProvidedWithoutQuantity(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('Each');
        $currency = 'USD';

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setCurrency($currency);

        $kitItem1 = new ProductKitItemStub(1);
        $kitItem1Product = (new ProductStub())
            ->setId(10);
        $kitItem1Quantity = 0.1234;

        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $kitItem2Quantity = null;

        $this->builder
            ->addKitItemProduct($kitItem1, $kitItem1Product, $productUnitItem, $kitItem1Quantity)
            ->addKitItemProduct($kitItem2, $kitItem2Product, $productUnitEach, $kitItem2Quantity);

        $throwable = new \InvalidArgumentException('Quantity must be numeric and more than or equal zero.');
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
                    'quantity' => null,
                    'currency' => $currency,
                ]
            );

        self::assertNull($this->builder->create());
    }

    public function testCreateWhenKitItemsProvided(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('Each');
        $quantity = 12.3456;
        $currency = 'USD';

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity)
            ->setCurrency($currency);

        $kitItem1Quantity = 0.1234;
        $kitItem1 = (new ProductKitItemStub(1))
            ->setProductUnit($productUnitItem)
            ->setMinimumQuantity($kitItem1Quantity);
        $kitItem1Product = (new ProductStub())
            ->setId(10);

        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $kitItem2Quantity = 0.5678;
        $this->builder
            ->addKitItemProduct($kitItem1, $kitItem1Product)
            ->addKitItemProduct($kitItem2, $kitItem2Product, $productUnitEach, $kitItem2Quantity);

        $this->assertLoggerNotCalled();

        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            $kitItem1Quantity,
            $currency
        );
        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitEach,
            $kitItem2Quantity,
            $currency
        );
        $productKitPriceCriteria = new ProductKitPriceCriteria(
            $product,
            $productUnitItem,
            $quantity,
            $currency
        );
        $productKitPriceCriteria
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);

        self::assertEquals($productKitPriceCriteria, $this->builder->create());
    }

    public function testCreateWhenKitItemsProvidedWithoutCurrency(): void
    {
        $product = (new ProductStub())->setId(10);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('Each');
        $quantity = 12.3456;
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $this->builder
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->setQuantity($quantity);

        $kitItem1Quantity = 0.1234;
        $kitItem1 = (new ProductKitItemStub(1))
            ->setProductUnit($productUnitItem)
            ->setMinimumQuantity($kitItem1Quantity);
        $kitItem1Product = (new ProductStub())
            ->setId(10);

        $kitItem2 = new ProductKitItemStub(2);
        $kitItem2Product = (new ProductStub())
            ->setId(20);
        $kitItem2Quantity = 0.5678;
        $this->builder
            ->addKitItemProduct($kitItem1, $kitItem1Product, $productUnitItem, $kitItem1Quantity)
            ->addKitItemProduct($kitItem2, $kitItem2Product, $productUnitEach, $kitItem2Quantity);

        $this->assertLoggerNotCalled();

        $kitItem1PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem1,
            $kitItem1Product,
            $productUnitItem,
            $kitItem1Quantity,
            $currency
        );
        $kitItem2PriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem2,
            $kitItem2Product,
            $productUnitEach,
            $kitItem2Quantity,
            $currency
        );
        $productKitPriceCriteria = new ProductKitPriceCriteria(
            $product,
            $productUnitItem,
            $quantity,
            $currency
        );
        $productKitPriceCriteria
            ->addKitItemProductPriceCriteria($kitItem1PriceCriteria)
            ->addKitItemProductPriceCriteria($kitItem2PriceCriteria);

        self::assertEquals($productKitPriceCriteria, $this->builder->create());
    }
}
