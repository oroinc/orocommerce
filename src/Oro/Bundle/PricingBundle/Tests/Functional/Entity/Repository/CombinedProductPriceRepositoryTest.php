<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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
        $this->initClient();
        $this->loadFixtures(
            [
                LoadCombinedPriceLists::class,
                LoadProductPrices::class,
                LoadCombinedProductPrices::class,
            ]
        );
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
                'product' => 'product-1',
                'expectedExists' => true,
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-2',
                'expectedExists' => true,
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2t_3f_1t',
                'product' => 'product-7',
                'expectedExists' => false,
            ],
        ];
    }

    /**
     * @depends      testInsertPricesByPriceList
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
                'product' => $this->getReference($product),
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
                'products' => ['product-1'],
                'currency' => 'USD',
            ],
            [
                'combinedPriceList' => '1t_2t_3t',
                'products' => ['product-2'],
            ],
            [
                'combinedPriceList' => '1t_2t_3t',
                'products' => ['product-1', 'product-2'],
            ],
        ];
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getCombinedProductPriceRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedProductPrice');
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListToPriceListRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedPriceListToPriceList');
    }

    public function testFindMinByWebsiteForFilter()
    {
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->getCombinedProductPriceRepository()
            ->findMinByWebsiteForFilter(
                $website->getId(),
                [$product1],
                $this->getReference('1f')->getId()
            );
        $expected = [
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '13.1000',
                'currency' => 'USD',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '0.0000',
                'currency' => 'USD',
                'unit' => 'milliliter',
                'cpl' => $this->getReference('1f')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    public function testFindMinByWebsiteForSort()
    {
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->getCombinedProductPriceRepository()
            ->findMinByWebsiteForSort(
                $website->getId(),
                [$product1],
                $this->getReference('1f')->getId()
            );
        $expected = [
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '0.0000',
                'currency' => 'USD',
                'cpl' => $this->getReference('1f')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $a
     * @param array $b
     * @return bool
     */
    protected function sort(array $a, array $b)
    {
        if ($a['cpl'] === $b['cpl'] && $a['currency'] === $b['currency']) {
            return $a['unit'] > $b['unit'] ? 1 : 0;
        } elseif ($a['cpl'] === $b['cpl']) {
            return $a['currency'] > $b['currency'] ? 1 : 0;
        }

        return $a['cpl'] > $b['cpl'] ? 1 : 0;
    }
}
