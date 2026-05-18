<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Provider\OffersFromRequestProductProvider;
use PHPUnit\Framework\TestCase;

final class OffersFromRequestProductProviderTest extends TestCase
{
    private OffersFromRequestProductProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new OffersFromRequestProductProvider();
    }

    public function testReturnsEmptyArrayWhenNoRequestProductItems(): void
    {
        self::assertSame([], $this->provider->getOffers(new RequestProduct()));
    }

    public function testIncludesItemWithoutPrice(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $item = new RequestProductItem();
        $item->setProductUnit($productUnit);
        $item->setProductUnitCode('item');
        $item->setQuantity(3.0);

        $requestProduct = new RequestProduct();
        $requestProduct->addRequestProductItem($item);

        $offers = $this->provider->getOffers($requestProduct);

        self::assertCount(1, $offers);
        self::assertSame('item', $offers[0]['unit']);
        self::assertSame(3.0, $offers[0]['quantity']);
        self::assertArrayNotHasKey('price', $offers[0]);
        self::assertArrayNotHasKey('currency', $offers[0]);
    }

    public function testIncludesItemWithPrice(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $price = Price::create('10.5', 'USD');

        $item = new RequestProductItem();
        $item->setProductUnit($productUnit);
        $item->setProductUnitCode('each');
        $item->setQuantity(2.0);
        $item->setPrice($price);

        $requestProduct = new RequestProduct();
        $requestProduct->addRequestProductItem($item);

        $offers = $this->provider->getOffers($requestProduct);

        self::assertCount(1, $offers);
        self::assertSame('each', $offers[0]['unit']);
        self::assertSame(2.0, $offers[0]['quantity']);
        self::assertSame('10.5', $offers[0]['price']);
        self::assertSame('USD', $offers[0]['currency']);
    }

    public function testExcludesItemWhenUnitNotAvailableForProduct(): void
    {
        $availableUnit = (new ProductUnit())->setCode('item');
        $availablePrecision = (new ProductUnitPrecision())->setUnit($availableUnit);

        $product = new Product();
        $product->addUnitPrecision($availablePrecision);

        $unavailableProductUnit = (new ProductUnit())->setCode('set');
        $item = new RequestProductItem();
        $item->setProductUnit($unavailableProductUnit);
        $item->setProductUnitCode('set');
        $item->setQuantity(1.0);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->addRequestProductItem($item);

        $offers = $this->provider->getOffers($requestProduct);

        self::assertSame([], $offers);
    }

    public function testIncludesItemWhenProductIsNull(): void
    {
        $productUnit = (new ProductUnit())->setCode('box');
        $item = new RequestProductItem();
        $item->setProductUnit($productUnit);
        $item->setProductUnitCode('box');
        $item->setQuantity(5.0);

        $requestProduct = new RequestProduct();
        $requestProduct->addRequestProductItem($item);

        $offers = $this->provider->getOffers($requestProduct);

        self::assertCount(1, $offers);
        self::assertSame('box', $offers[0]['unit']);
    }

    public function testIncludesItemWhenUnitIsAvailableForProduct(): void
    {
        $productUnit = (new ProductUnit())->setCode('kg');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);

        $product = new Product();
        $product->addUnitPrecision($unitPrecision);

        $item = new RequestProductItem();
        $item->setProductUnit($productUnit);
        $item->setProductUnitCode('kg');
        $item->setQuantity(4.0);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->addRequestProductItem($item);

        $offers = $this->provider->getOffers($requestProduct);

        self::assertCount(1, $offers);
        self::assertSame('kg', $offers[0]['unit']);
        self::assertSame(4.0, $offers[0]['quantity']);
    }
}
