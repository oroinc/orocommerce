<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceListToProductRepositoryTest extends WebTestCase
{
    /**
     * @var PriceListToProductRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices',
            ]
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceListToProduct');
    }
    
    public function testGetProductsWithoutPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');

        // Assert that there are 4 assigned products for given price list
        $this->assertCount(4, $this->repository->findBy(['priceList' => $priceList]));
        $actual = array_map(
            function (Product $product) {
                return $product->getId();
            },
            iterator_to_array($this->repository->getProductsWithoutPrices($priceList))
        );
        // Check that 2 products does not have prices
        $expected = [
            $this->getReference('product.3')->getId(),
            $this->getReference('product.4')->getId(),
        ];
        $this->assertEquals($expected, $actual);
    }
}
