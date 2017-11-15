<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Provider;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductUpcomingData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductUpcomingProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductUpcomingData::class]);
    }

    public function testIsUpcoming()
    {
        $provider = $this->getProductUpcomingProvider();

        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_1)));
        $this->assertFalse($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_2)));
        $this->assertTrue($provider->isUpcoming($this->getReference(LoadProductData::PRODUCT_5)));
    }

    public function testGetAvailabilityDate()
    {
        $provider = $this->getProductUpcomingProvider();

        $this->assertEquals(new \DateTime('2070-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_1)
        ));

        $this->assertNull($provider->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_3)));

        $this->assertEquals(new \DateTime('2050-10-10'), $provider->getAvailabilityDate(
            $this->getReference(LoadProductData::PRODUCT_5)
        ));
    }

    public function testGetLatestAvailabilityDate()
    {
        $products = [
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadProductData::PRODUCT_2),
            $this->getReference(LoadProductData::PRODUCT_5),
        ];

        $this->assertEquals(
            new \DateTime('2070-10-10'),
            $this->getProductUpcomingProvider()->getLatestAvailabilityDate($products)
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetAvailabilityDateForNonUpcomingProduct()
    {
        $this->getProductUpcomingProvider()->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_2));
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetAvailabilityDateForNonUpcomingProduct2()
    {
        $this->getProductUpcomingProvider()->getAvailabilityDate($this->getReference(LoadProductData::PRODUCT_6));
    }

    /**
     * @return ProductUpcomingProvider
     */
    public function getProductUpcomingProvider()
    {
        return $this->getContainer()->get('oro_inventory.provider.product_upcoming_provider');
    }
}
