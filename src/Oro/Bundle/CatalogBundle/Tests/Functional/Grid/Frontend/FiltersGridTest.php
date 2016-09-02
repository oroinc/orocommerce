<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Grid\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class FiltersGridTest extends WebTestCase
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

        $this->loadFixtures(['Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);
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
}
