<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class CombinedPriceListToPriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
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
        $priceListsRelations = $this->getRepository()->getPriceListsByCombinedAndProduct($combinedPriceList, $product);

        if ($expectedPriceLists) {
            foreach ($priceListsRelations as $priceListsRelation) {
                $exists = in_array($priceListsRelation->getPriceList()->getName(), $expectedPriceLists);
                $this->assertEquals(true, $exists, 'Price lists not found');
            }
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
                'combinedPriceList' => '1t_2f_3t',
                'product' => 'product.1',
                'expectedPriceLists' => ['priceList1', 'priceList2'],
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2f_3t',
                'product' => 'product.2',
                'expectedPriceLists' => ['priceList1', 'priceList2'],
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '3f_4t_2f',
                'product' => 'product.3',
                'expectedPriceLists' => [],
            ],
        ];
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:CombinedPriceListToPriceList');
    }
}
