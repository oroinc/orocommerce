<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadCustomProductUnits;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductShippingOptionsRepositoryTest extends WebTestCase
{
    protected ProductShippingOptionsRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductShippingOptions::class,
            LoadCustomProductUnits::class,
        ]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(ProductShippingOptions::class);
    }

    public function testFindIndexedByProductsAndUnits(): void
    {
        $product = $this->getReference('product-1');
        $unit = $this->getReference('product_unit.liter');

        $unitsByProductIds = [$product->getId() => ['liter' => $unit]];

        $shippingOptionsArray = $this->repository->findIndexedByProductsAndUnits($unitsByProductIds);

        $expectedShippingOpts = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->assertEquals(
            [
                $expectedShippingOpts->getProduct()->getId() => [
                    'liter' => [
                        'dimensionsHeight' => 3,
                        'dimensionsLength' => 1,
                        'dimensionsWidth' => 2,
                        'dimensionsUnit' => 'in',
                        'weightUnit' => 'kilo',
                        'weightValue' => 42,
                        'code' => 'liter'
                    ]
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
        $unit1 = $this->getReference(LoadProductUnits::BOX);
        $product2 = $this->getReference('product-2');
        $unit2 = $this->getReference(LoadProductUnits::BOTTLE);
        $unit3 = $this->getReference(LoadCustomProductUnits::WITH_SPECIAL_CHAR);

        $unitsByProductIds = [
            $product1->getId() => ['box' => $unit1],
            $product2->getId() => ['bottle' => $unit2],
            $product2->getId() => ['mètre' => $unit3],
        ];

        $shippingOptionsArray = $this->repository->findIndexedByProductsAndUnits($unitsByProductIds);

        $this->assertEquals([], $shippingOptionsArray);
    }

    public function testFindIndexedByProductsAndUnitsMultipleUnits(): void
    {
        $product1 = $this->getReference('product-1');
        $unit11 = $this->getReference('product_unit.bottle');
        $unit12 = $this->getReference('product_unit.liter');
        $product2 = $this->getReference('product-2');
        $unit2 = $this->getReference('product_unit.box');

        $unitsByProductIds = [
            $product1->getId() => [
                'bottle' => $unit11,
                'liter' => $unit12
            ],
            $product2->getId() => [
                'box' => $unit2
            ],
        ];

        $shippingOptionsArray = $this->repository->findIndexedByProductsAndUnits($unitsByProductIds);

        $this->assertEquals([
            $product1->getId() => [
                "bottle" => [
                    "dimensionsHeight" => 10.0,
                    "dimensionsLength" => 10.0,
                    "dimensionsWidth" => 10.0,
                    "dimensionsUnit" => "ft",
                    "weightUnit" => "pound",
                    "weightValue" => 5.0,
                    "code" => "bottle",
                ],
                "liter" => [
                    "dimensionsHeight" => 3.0,
                    "dimensionsLength" => 1.0,
                    "dimensionsWidth" => 2.0,
                    "dimensionsUnit" => "in",
                    "weightUnit" => "kilo",
                    "weightValue" => 42.0,
                    "code" => "liter",
                ]
            ]
        ], $shippingOptionsArray);
    }
}
