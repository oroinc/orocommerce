<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Provider\ProductPriceEntityNameProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductPriceEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductPriceEntityNameProvider();
    }

    public function testGetNameForShortFormat(): void
    {
        $this->assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new ProductPrice()));
        $this->assertFalse($this->provider->getName(null, 'en', new ProductPrice()));
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForLocale(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getEntity(Product::class, ['sku' => 'SKU123']))
            ->setPriceList($this->getEntity(PriceList::class, ['id' => 42]))
            ->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']))
            ->setQuantity(456.7)
            ->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123 | 456.7 item | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameForLocaleNoProduct(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setPriceList($this->getEntity(PriceList::class, ['id' => 42]))
            ->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']))
            ->setQuantity(456.7)
            ->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            '456.7 item | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameForLocaleNoPriceList(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getEntity(Product::class, ['sku' => 'SKU123']))
            ->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']))
            ->setQuantity(456.7)
            ->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123 | 456.7 item | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameForLocaleNoQuantity(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getEntity(Product::class, ['sku' => 'SKU123']))
            ->setPriceList($this->getEntity(PriceList::class, ['id' => 42]))
            ->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']))
            ->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123 | item | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameForLocaleNoUnit(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getEntity(Product::class, ['sku' => 'SKU123']))
            ->setPriceList($this->getEntity(PriceList::class, ['id' => 42]))
            ->setQuantity(456.7)
            ->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123 | 456.7 | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameForLocaleNoPrice(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getEntity(Product::class, ['sku' => 'SKU123']))
            ->setPriceList($this->getEntity(PriceList::class, ['id' => 42]))
            ->setUnit($this->getEntity(ProductUnit::class, ['code' => 'item']))
            ->setQuantity(456.7);

        $this->assertEquals(
            'SKU123 | 456.7 item',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameDQL(): void
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', ProductPrice::class, 'productPrice')
        );
    }
}
