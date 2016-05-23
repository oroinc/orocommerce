<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class CombinedProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
        $this->insertFromSelectQueryExecutor = $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }

    /**
     * @dataProvider insertPricesByPriceListDataProvider
     * @param string $combinedPriceList
     * @param string $product
     * @param boolean $expectedExists
     */
    public function testInsertPricesByPriceList($combinedPriceList, $product, $expectedExists)
    {
        /**
         * @var CombinedPriceList $combinedPriceList
         */
        $combinedPriceList = $this->getReference($combinedPriceList);
        /** @var Product $product */
        $product = $this->getReference($product);

        $repository = $this->getCombinedPriceListToPriceListRepository();
        $combinedPriceListRelations = $repository->getPriceListRelations($combinedPriceList, $product);

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();

        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $product);
        $prices = $combinedProductPriceRepository->findBy(
            [
                'priceList' => $combinedPriceList,
                'product' => $product,
            ]
        );
        $this->assertEmpty($prices);
        foreach ($combinedPriceListRelations as $combinedPriceListRelation) {
            $combinedProductPriceRepository->insertPricesByPriceList(
                $this->insertFromSelectQueryExecutor,
                $combinedPriceList,
                $combinedPriceListRelation->getPriceList(),
                $combinedPriceListRelation->isMergeAllowed(),
                $product
            );
        }
        $prices = $combinedProductPriceRepository->findBy(
            [
                'priceList' => $combinedPriceList,
                'product' => $product,
            ]
        );
        if ($expectedExists) {
            $this->assertNotEmpty($prices);
        } else {
            $this->assertEmpty($prices);
        }

    }

    /**
     * @return array
     */
    public function insertPricesByPriceListDataProvider()
    {
        return [
            'test getting price lists 1' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product.1',
                'expectedExists' => true,
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product.2',
                'expectedExists' => true,
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2t_3f_1t',
                'product' => 'product.7',
                'expectedExists' => false,
            ],
        ];
    }

    /**
     * @depends testInsertPricesByPriceList
     * @dataProvider getPricesForProductsByPriceListDataProvider
     * @param string $priceList
     * @param array $products
     * @param string|null $currency
     */
    public function testGetPricesForProductsByPriceList($priceList, array $products, $currency = null)
    {
        /**
         * @var CombinedPriceList $priceList
         */
        $priceList = $this->getReference($priceList);
        $productIds = array_map(
            function ($product) {
                return $this->getReference($product)->getId();
            },
            $products
        );

        $expected = [];
        foreach ($products as $product) {
            $searchConditions = [
                'priceList' => $priceList,
                'product' => $this->getReference($product)
            ];
            if ($currency) {
                $searchConditions['currency'] = $currency;
            }
            $expected = array_merge(
                $expected,
                $this->getCombinedProductPriceRepository()->findBy($searchConditions)
            );
        }

        $result = $this->getCombinedProductPriceRepository()
            ->getPricesForProductsByPriceList($priceList, $productIds, $currency);

        $this->assertCount(count($expected), $result);
        foreach ($expected as $price) {
            $this->assertContains($price, $result);
        }
    }

    /**
     * @return array
     */
    public function getPricesForProductsByPriceListDataProvider()
    {
        return [
            [
                'combinedPriceList' => '1t_2t_3t',
                'products' => ['product.1'],
                'currency' => 'USD'
            ],
            [
                'combinedPriceList' => '1t_2t_3t',
                'products' => ['product.2']
            ],
            [
                'combinedPriceList' => '1t_2t_3t',
                'products' => ['product.1', 'product.2']
            ],
        ];
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getCombinedProductPriceRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice');
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListToPriceListRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToPriceList');
    }
}
