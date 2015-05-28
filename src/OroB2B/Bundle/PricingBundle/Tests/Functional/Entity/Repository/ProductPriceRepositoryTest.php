<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class ProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:ProductPrice');
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');
        /** @var Product $notRemovedProduct */
        $notRemovedProduct = $this->getReference('product.2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.kg');
        /** @var ProductUnit $unit */
        $notRemovedUnit = $this->getReference('product_unit.item');

        $this->repository->deleteByProductUnit($product, $unit);

        $this->assertEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $notRemovedProduct,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $notRemovedUnit
                ]
            )
        );
    }
}
