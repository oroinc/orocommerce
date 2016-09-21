<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Grid\Frontend;

use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;

/**
 * @dbIsolation
 */
class ProductSearchGridTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData'
            ]
        );
    }

    public function testSorters()
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_ASC]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_DESC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[name]' => AbstractSorterExtension::DIRECTION_ASC,]
        );
        $this->checkSorting($products, 'name', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[name]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'name', AbstractSorterExtension::DIRECTION_DESC);
    }

    /**
     * @param array  $data
     * @param string $column
     * @param string $orderDirection
     * @param bool   $stringSorting
     */
    protected function checkSorting(array $data, $column, $orderDirection, $stringSorting = false)
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        foreach ($data as $row) {
            $actualValue = $row[$column];

            if (isset($lastValue)) {
                if ($orderDirection === AbstractSorterExtension::DIRECTION_DESC) {
                    $this->assertGreaterThanOrEqual($actualValue, $lastValue);
                } elseif ($orderDirection === AbstractSorterExtension::DIRECTION_ASC) {
                    $this->assertLessThanOrEqual($actualValue, $lastValue);
                }
            }
            $lastValue = $actualValue;
        }
    }

    public function testFilters()
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        $data = $this->getDatagridData(
            'frontend-product-search-grid'
        );

        $firstRow = array_shift($data);
        $countWithoutFilters = count($data);

        $filteredData = $this->getDatagridData(
            'frontend-product-search-grid',
            [
                '[sku][value]' => $firstRow['sku'],
                '[sku][type]' => '1',
                '[name][value]' => $firstRow['name'],
                '[name][type]' => '1',
            ]
        );

        $this->assertTrue($countWithoutFilters > count($filteredData));

        $firstFilteredRow = array_shift($filteredData);

        $this->assertEquals($firstRow['sku'], $firstFilteredRow['sku']);
        $this->assertEquals($firstRow['name'], $firstFilteredRow['name']);
    }

    public function testAllTextFilter()
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        $data = $this->getDatagridData(
            'frontend-product-search-grid'
        );

        $firstRow = array_shift($data);
        $allTextValue = substr($firstRow['name'], 1, -1);

        $this->assertNotEmpty($allTextValue);

        $filteredData = $this->getDatagridData(
            'frontend-product-search-grid',
            [
                '[sku][all_text]' => $allTextValue,
                '[sku][type]' => '1'
            ]
        );

        $found = false;

        foreach ($filteredData as $row) {
            if ($row['name'] == $firstRow['name']) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    public function testPagination()
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        $first2Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 1,
            '[_per_page]' => 2,
        ]);
        $first2Skus = array_column($first2Rows, 'sku');

        $second2Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 2,
            '[_per_page]' => 2,
        ]);
        $second2Skus = array_column($second2Rows, 'sku');

        $first4Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 1,
            '[_per_page]' => 4,
        ]);
        $first4Skus = array_column($first4Rows, 'sku');

        $this->assertEquals($first4Skus, array_merge($first2Skus, $second2Skus));
    }

    /**
     * @param string $gridName
     * @param array  $filters
     * @param array  $sorters
     * @param array  $pager
     * @return array
     */
    protected function getDatagridData($gridName, array $filters = [], array $sorters = [], array $pager = [])
    {
        $this->markTestSkipped('Enable after real V2 search engine is implemented');

        $gridParameters = ['gridName' => $gridName];

        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$gridName . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$gridName . '[_sort_by]' . $sorter] = $value;
        }
        foreach ($pager as $param => $value) {
            $result[$gridName . '[_pager]' . $param] = $value;
        }

        $response = $this->client->requestFrontendGrid($gridParameters, $result);

        return json_decode($response->getContent(), true)['data'];
    }
}
