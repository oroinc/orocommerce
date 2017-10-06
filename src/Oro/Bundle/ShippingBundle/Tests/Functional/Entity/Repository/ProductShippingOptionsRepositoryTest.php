<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductShippingOptionsRepositoryTest extends WebTestCase
{
    /**
     * @var ProductShippingOptionsRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductShippingOptions::class
        ]);

        $this->repository = static::getContainer()->get('oro_shipping.repository.product_shipping_options');
    }

    public function testFindByProductsAndProductUnits()
    {
        $options = $this->repository->findByProductsAndProductUnits(
            [
                $this->getReference('product-1'),
                $this->getReference('product-1')
            ],
            [
                $this->getReference('product_unit.liter'),
                $this->getReference('product_unit.bottle')
            ]
        );

        static::assertTrue(in_array(
            $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1),
            $options,
            false
        ));

        static::assertTrue(in_array(
            $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2),
            $options,
            false
        ));
    }
}
