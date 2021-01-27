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
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductShippingOptions::class,
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(ProductShippingOptions::class);
    }

    public function testFindIndexedByProductsAndUnits(): void
    {
        $product = $this->getReference('product-1');
        $unit = $this->getReference('product_unit.liter');

        $unitsByProductIds = [$product->getId() => $unit];

        $shippingOptionsArray = $this->repository->findIndexedByProductsAndUnits($unitsByProductIds);

        $expectedShippingOptions = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->assertEquals(
            [
                $expectedShippingOptions->getProduct()->getId() => [
                    'dimensionsHeight' => 3,
                    'dimensionsLength' => 1,
                    'dimensionsWidth' => 2,
                    'dimensionsUnit' => 'in',
                    'weightUnit' => 'kilo',
                    'weightValue' => 42,
                    'productId' => $expectedShippingOptions->getProduct()->getId(),
                ]
            ],
            $shippingOptionsArray
        );
    }

    public function testFindIndexedByProductsAndUnitsEmpty(): void
    {
        $this->assertEquals([], $this->repository->findIndexedByProductsAndUnits([]));
    }

    public function testFindIndexedByProductsAndUnitsDifferentUnits(): void
    {
        $product1 = $this->getReference('product-1');
        $unit1 = $this->getReference('product_unit.box');
        $product2 = $this->getReference('product-2');
        $unit2 = $this->getReference('product_unit.bottle');

        $unitsByProductIds = [
            $product1->getId() => $unit1,
            $product2->getId() => $unit2,
        ];

        $shippingOptionsArray = $this->repository->findIndexedByProductsAndUnits($unitsByProductIds);

        $this->assertEquals([], $shippingOptionsArray);
    }
}
