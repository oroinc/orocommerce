<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Grid\Frontend;

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
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => 'frontend-product-search-grid'
            ],
            [],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);

        $data = $result['data'];

        $firstRow = array_shift($data);
        $countWithoutFilters = count($data);

        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => 'frontend-product-search-grid'
            ],
            [
                'frontend-product-search-grid[_filter][sku][value]' => $firstRow['sku'],
                'frontend-product-search-grid[_filter][sku][type]' => '1',
                'frontend-product-search-grid[_filter][name][value]' => $firstRow['name'],
                'frontend-product-search-grid[_filter][name][type]' => '1',
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);

        $filteredData = $result['data'];

        $this->assertTrue($countWithoutFilters > count($filteredData));

        $firstFilteredRow = array_shift($filteredData);

        $this->assertEquals($firstRow['sku'], $firstFilteredRow['sku']);
        $this->assertEquals($firstRow['name'], $firstFilteredRow['name']);
    }

    public function testAllTextFilter()
    {
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => 'frontend-product-search-grid'
            ],
            [],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);

        $data = $result['data'];

        $firstRow = array_shift($data);
        $allTextValue = substr($firstRow['name'], 1, -1);

        $this->assertNotEmpty($allTextValue);

        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => 'frontend-product-search-grid'
            ],
            [
                'frontend-product-search-grid[_filter][sku][all_text]' => $firstRow['sku'],
                'frontend-product-search-grid[_filter][sku][type]' => '1',
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);

        $filteredData = $result['data'];
        $found = false;

        foreach ($filteredData as $row) {
            if ($row['name'] == $firstRow['name']) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * @param string $gridName
     * @param array  $filters
     * @param array  $sorters
     * @return array
     */
    protected function getDatagridData($gridName, array $filters = [], array $sorters = [])
    {
        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$gridName . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$gridName . '[_sort_by]' . $sorter] = $value;
        }
        $response = $this->client->requestFrontendGrid(['gridName' => $gridName], $result);

        return json_decode($response->getContent(), true)['data'];
    }
}
