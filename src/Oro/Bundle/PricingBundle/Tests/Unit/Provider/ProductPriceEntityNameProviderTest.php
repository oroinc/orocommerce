<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Provider\ProductPriceEntityNameProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\ReflectionUtil;

class ProductPriceEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProductPriceEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ProductPriceEntityNameProvider();
    }

    private function getProduct(string $sku): Product
    {
        $product = new Product();
        $product->setSku($sku);

        return $product;
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForShortFormat(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getProduct('SKU123'));
        $productPrice->setPriceList($this->getPriceList(42));
        $productPrice->setUnit($this->getProductUnit('item'));
        $productPrice->setQuantity(456.7);
        $productPrice->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123',
            $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', $productPrice)
        );
    }

    public function testGetName(): void
    {
        $productPrice = new ProductPrice();
        $productPrice->setProduct($this->getProduct('SKU123'));
        $productPrice->setPriceList($this->getPriceList(42));
        $productPrice->setUnit($this->getProductUnit('item'));
        $productPrice->setQuantity(456.7);
        $productPrice->setPrice(Price::create(1001.2, 'USD'));

        $this->assertEquals(
            'SKU123 | 456.7 item | 1001.2 USD',
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $productPrice)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQLForShortFormat(): void
    {
        $this->assertEquals(
            'price.productSku',
            $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', ProductPrice::class, 'price')
        );
    }

    public function testGetNameDQL(): void
    {
        $this->assertEquals(
            '(SELECT CONCAT(price_p.productSku, \' | \','
            . ' CAST(price_p.quantity AS string), \' \', price_u.code, \' | \','
            . ' TRIM(TRAILING \'.\' FROM TRIM(TRAILING \'0\' FROM CAST(price_p.value AS string))),'
            . ' \' \', price_p.currency)'
            . ' FROM Oro\Bundle\PricingBundle\Entity\ProductPrice price_p INNER JOIN price_p.unit price_u'
            . ' WHERE price_p = price)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', ProductPrice::class, 'price')
        );
    }
}
