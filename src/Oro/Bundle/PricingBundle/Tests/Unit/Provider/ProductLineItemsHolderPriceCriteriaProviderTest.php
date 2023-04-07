<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderPriceCriteriaProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Model\ShoppingListLineItemsHolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductLineItemsHolderPriceCriteriaProviderTest extends TestCase
{
    private const USD = 'USD';
    private const EUR = 'EUR';

    private ProductLineItemsHolderCurrencyProvider|MockObject $lineItemsHolderCurrencyProvider;

    private ProductLineItemsHolderPriceCriteriaProvider $provider;

    protected function setUp(): void
    {
        $this->lineItemsHolderCurrencyProvider = $this->createMock(ProductLineItemsHolderCurrencyProvider::class);

        $this->provider = new ProductLineItemsHolderPriceCriteriaProvider($this->lineItemsHolderCurrencyProvider);
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNoCurrencyNoLineItems(): void
    {
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection());
        $this->lineItemsHolderCurrencyProvider
            ->expects(self::once())
            ->method('getCurrencyForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn(self::USD);

        self::assertEquals([], $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder));
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNoCurrencyNoSupportedLineItems(): void
    {
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([new \stdClass()]));
        $this->lineItemsHolderCurrencyProvider
            ->expects(self::once())
            ->method('getCurrencyForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn(self::USD);

        self::assertEquals([], $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder));
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNoCurrencyHasLineItems(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));
        $this->lineItemsHolderCurrencyProvider
            ->expects(self::once())
            ->method('getCurrencyForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn(self::USD);

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::USD
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::USD
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNoProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenDisabledProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_DISABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNewProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWhenNoUnitCode(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit(new ProductUnit())
            ->setQuantity(12.345);
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWithKitItemLineItemsWhenNoCurrency(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20011)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(1.234);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20012)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(2.345);
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));
        $this->lineItemsHolderCurrencyProvider
            ->expects(self::once())
            ->method('getCurrencyForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn(self::USD);

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::USD
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::USD
                ),
                spl_object_hash($kitItemLineItem1) => new ProductPriceCriteria(
                    $kitItemLineItem1->getProduct(),
                    $kitItemLineItem1->getProductUnit(),
                    (float)$kitItemLineItem1->getQuantity(),
                    self::USD
                ),
                spl_object_hash($kitItemLineItem2) => new ProductPriceCriteria(
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    (float)$kitItemLineItem2->getQuantity(),
                    self::USD
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWithKitItemLineItemsWhenNoProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(1.234);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20012)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(2.345);
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($kitItemLineItem2) => new ProductPriceCriteria(
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    (float)$kitItemLineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWithKitItemLineItemsWhenDisabledProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20011)->setStatus(Product::STATUS_DISABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(1.234);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20012)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(2.345);
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($kitItemLineItem2) => new ProductPriceCriteria(
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    (float)$kitItemLineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWithKitItemLineItemsWhenNewProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(1.234);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20012)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(2.345);
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($kitItemLineItem2) => new ProductPriceCriteria(
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    (float)$kitItemLineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }

    public function testGetProductPriceCriteriaForLineItemsHolderWithKitItemLineItemsWhenNoUnitCode(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(12.345);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20011)->setStatus(Product::STATUS_ENABLED))
            ->setUnit(new ProductUnit())
            ->setQuantity(1.234);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20012)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(2.345);
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setUnit((new ProductUnit())->setCode('item'))
            ->setQuantity(34.567)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $lineItemsHolder = new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->lineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method('getCurrencyForLineItemsHolder');

        self::assertEquals(
            [
                spl_object_hash($lineItem1) => new ProductPriceCriteria(
                    $lineItem1->getProduct(),
                    $lineItem1->getProductUnit(),
                    (float)$lineItem1->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($lineItem2) => new ProductPriceCriteria(
                    $lineItem2->getProduct(),
                    $lineItem2->getProductUnit(),
                    (float)$lineItem2->getQuantity(),
                    self::EUR
                ),
                spl_object_hash($kitItemLineItem2) => new ProductPriceCriteria(
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    (float)$kitItemLineItem2->getQuantity(),
                    self::EUR
                ),
            ],
            $this->provider->getProductPriceCriteriaForLineItemsHolder($lineItemsHolder, self::EUR)
        );
    }
}
