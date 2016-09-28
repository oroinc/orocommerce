<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class CombinedPriceListToPriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
    }

    /**
     * @dataProvider priceListsByCombinedAndProductDataProvider
     * @param string $combinedPriceList
     * @param string $product
     * @param array $expectedPriceLists
     */
    public function testGetPriceListsByCombinedAndProduct($combinedPriceList, $product, $expectedPriceLists)
    {
        /**
         * @var $combinedPriceList CombinedPriceList
         * @var $product Product
         */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $product = $this->getReference($product);
        $priceListsRelations = $this->getRepository()->getPriceListRelations($combinedPriceList, $product);

        if ($expectedPriceLists) {
            $actualPriceLists = array_map(
                function (CombinedPriceListToPriceList $relation) {
                    return $relation->getPriceList()->getId();
                },
                $priceListsRelations
            );
            $expectedPriceLists = array_map(
                function ($priceListReference) {
                    return $this->getReference($priceListReference)->getId();
                },
                $expectedPriceLists
            );
            $this->assertEquals($expectedPriceLists, $actualPriceLists);
        } else {
            $this->assertEmpty($priceListsRelations);
        }
    }

    /**
     * @return array
     */
    public function priceListsByCombinedAndProductDataProvider()
    {
        return [
            'test getting price lists 1' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product.1',
                'expectedPriceLists' => ['price_list_1', 'price_list_2'],
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product.2',
                'expectedPriceLists' => ['price_list_1', 'price_list_2'],
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2f_1t_3t',
                'product' => 'product.7',
                'expectedPriceLists' => [],
            ],
        ];
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedPriceListToPriceList');
    }
}
