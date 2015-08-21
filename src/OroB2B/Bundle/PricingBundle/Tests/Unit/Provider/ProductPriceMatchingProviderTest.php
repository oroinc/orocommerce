<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Provider\ProductPriceMatchingProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceMatchingProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var ProductPriceMatchingProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->productPriceClass = 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice';

        $this->provider = new ProductPriceMatchingProvider($this->registry, $this->productPriceClass);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->productPriceClass, $this->provider);
    }

    public function testMatchPrice()
    {
        $currency = 'USD';
        $price = $this->provider->matchPrice(new Product(), new ProductUnit(), 1, $currency);

        $this->assertInstanceOf('Oro\Bundle\CurrencyBundle\Model\Price', $price);
        $this->assertInternalType('float', $price->getValue());
        $this->assertEquals($currency, $price->getCurrency());
    }
}
