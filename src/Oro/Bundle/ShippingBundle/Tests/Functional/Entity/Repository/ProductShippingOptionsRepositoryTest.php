<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductShippingOptionsRepositoryTest extends WebTestCase
{
    /**
     * @var ProductShippingOptionsRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductShippingOptions::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')->getRepository(ProductShippingOptions::class);
    }

    public function testFindByProductsAndUnits()
    {
        $product = $this->getReference('product-1');
        $unit = $this->getReference('product_unit.liter');

        $unitsByProductIds = [$product->getId() => $unit];

        $shippingOptionsArray = $this->repository->findByProductsAndUnits($unitsByProductIds);

        $expectedShippingOptionsArray = [$this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)];

        static::assertEquals($expectedShippingOptionsArray, $shippingOptionsArray);
    }

    public function testFindByProductsAndUnitsEmpty()
    {
        $unitsByProductIds = [];

        $shippingOptionsArray = $this->repository->findByProductsAndUnits($unitsByProductIds);

        static::assertEquals([], $shippingOptionsArray);
    }

    public function testFindByProductsAndUnitsDifferentUnits()
    {
        $product1 = $this->getReference('product-1');
        $unit1 = $this->getReference('product_unit.box');
        $product2 = $this->getReference('product-2');
        $unit2 = $this->getReference('product_unit.bottle');

        $unitsByProductIds = [
            $product1->getId() => $unit1,
            $product2->getId() => $unit2,
        ];

        $shippingOptionsArray = $this->repository->findByProductsAndUnits($unitsByProductIds);

        static::assertEquals([], $shippingOptionsArray);
    }
}
