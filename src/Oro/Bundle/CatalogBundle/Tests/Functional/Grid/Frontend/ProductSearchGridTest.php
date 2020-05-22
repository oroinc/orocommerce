<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Grid\Frontend;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class ProductSearchGridTest extends FrontendWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->setCurrentWebsite('default');

        $this->loadFixtures([LoadFrontendCategoryProductData::class]);

        // load image filters for grid images
        $this->getContainer()->get('oro_layout.loader.image_filter')->load();
    }

    public function testSorters()
    {
        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_ASC]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_DESC);

        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [],
            ['[names]' => AbstractSorterExtension::DIRECTION_ASC,]
        );
        $this->checkSorting($products, 'names', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [],
            ['[names]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'names', AbstractSorterExtension::DIRECTION_DESC);
    }

    /**
     * @param array  $data
     * @param string $column
     * @param string $orderDirection
     */
    protected function checkSorting(array $data, $column, $orderDirection)
    {
        $this->assertNotEmpty($data);

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
        $data = $this->getDatagridData(
            ProductController::GRID_NAME
        );

        $indexes = array_keys($data);
        $lastRow = $data[end($indexes)];

        $filteredData = $this->getDatagridData(
            ProductController::GRID_NAME,
            [
                '[sku][value]' => $lastRow['sku'],
                '[sku][type]' => '1',
                '[names][value]' => $lastRow['name'],
                '[names][type]' => '1',
            ]
        );

        // can't use strict comparing because of different search engines
        $this->assertGreaterThanOrEqual(1, count($filteredData));

        $filteredRow = $this->getRowBySku($filteredData, $lastRow['sku']);

        $this->assertNotNull($filteredRow);
        $this->assertEquals($lastRow['sku'], $filteredRow['sku']);
        $this->assertEquals($lastRow['name'], $filteredRow['name']);
    }

    /**
     * @dataProvider dataProviderForFiltersWithForceLikeOption
     *
     * @param string $filter
     * @param string $field
     * @param string $searchString
     * @param array|string $expectedFieldValue
     */
    public function testGridFiltersWithForceLikeOption($filter, $field, $searchString, $expectedFieldValue)
    {
        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [
                sprintf('[%s][value]', $filter) => $searchString,
                sprintf('[%s][type]', $filter) => 1
            ]
        );

        $expectedFieldValues = [];
        foreach ($products as $product) {
            $expectedFieldValues[] = $product[$field];
        }

        $this->assertNotEmpty($expectedFieldValues);
        if (is_array($expectedFieldValue)) {
            foreach ($expectedFieldValue as $expected) {
                $this->assertContains($expected, $expectedFieldValues);
            }
        } else {
            $this->assertContains($expectedFieldValue, $expectedFieldValues);
        }
    }

    /**
     * @return array
     */
    public function dataProviderForFiltersWithForceLikeOption()
    {
        return [
            'sku' => [
                'filter' => 'sku',
                'field' => 'sku',
                'searchString' => substr(LoadProductData::PRODUCT_1, 0, 5),
                'expectedFieldValue' => LoadProductData::PRODUCT_1
            ],
            'names filter' => [
                'filter' => 'names',
                'field' => 'name',
                'searchString' => substr(LoadProductData::PRODUCT_1_DEFAULT_NAME, 0, 12),
                'expectedFieldValue' => LoadProductData::PRODUCT_1_DEFAULT_NAME
            ]
        ];
    }

    /**
     * @dataProvider dataProviderForDoesNotContainFilter
     *
     * @param string $filter
     * @param string $searchString
     * @param array $expectedFieldValues
     */
    public function testDoesNotContainFilterWithForceLikeOption($filter, $searchString, $expectedFieldValues)
    {
        $products = $this->getDatagridData(
            ProductController::GRID_NAME,
            [
                sprintf('[%s][value]', $filter) => $searchString,
                sprintf('[%s][type]', $filter) => 2
            ]
        );

        $actualFieldValues = [];
        foreach ($products as $product) {
            $actualFieldValues[] = $product[$filter];
        }

        $this->assertEquals(count($actualFieldValues), count($expectedFieldValues));
        foreach ($expectedFieldValues as $expected) {
            $this->assertContains($expected, $expectedFieldValues);
        }
    }

    /**
     * @return array
     */
    public function dataProviderForDoesNotContainFilter()
    {
        return [
            'sku not like' => [
                'sku',
                LoadProductData::PRODUCT_8,
                [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9,
                ],
            ],
            'sku not like inside' => [
                'sku', substr(LoadProductData::PRODUCT_8, 0, 4), [
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9,
                ],
            ],
            'names not like inside' => [
                'names', substr(LoadProductData::PRODUCT_1_DEFAULT_NAME, 0, 12), [
                    LoadProductData::PRODUCT_2_DEFAULT_NAME,
                    LoadProductData::PRODUCT_3_DEFAULT_NAME,
                    LoadProductData::PRODUCT_6_DEFAULT_NAME,
                    LoadProductData::PRODUCT_7_DEFAULT_NAME,
                    LoadProductData::PRODUCT_8_DEFAULT_NAME,
                    LoadProductData::PRODUCT_9_DEFAULT_NAME,
                ],
            ],
        ];
    }

    public function testAllTextFilter()
    {
        $data = $this->getDatagridData(
            ProductController::GRID_NAME
        );

        $indexes = array_keys($data);
        $lastRow = $data[end($indexes)];
        $allTextValue = substr($lastRow['shortDescription'], 0, 12);

        $filteredData = $this->getDatagridData(
            ProductController::GRID_NAME,
            [
                '[all_text][value]' => $allTextValue,
                '[all_text][type]' => '1'
            ]
        );

        // can't use strict comparing because of different search engines
        $this->assertGreaterThanOrEqual(1, count($filteredData));

        $filteredRow = $this->getRowBySku($filteredData, $lastRow['sku']);
        $this->assertStringStartsWith($allTextValue, $filteredRow['shortDescription']);
    }

    public function testPagination()
    {
        $first2Rows = $this->getDatagridData(ProductController::GRID_NAME, [], [], [
            '[_page]'     => 1,
            '[_per_page]' => 2,
        ]);
        $first2Skus = array_column($first2Rows, 'sku');

        $second2Rows = $this->getDatagridData(ProductController::GRID_NAME, [], [], [
            '[_page]'     => 2,
            '[_per_page]' => 2,
        ]);
        $second2Skus = array_column($second2Rows, 'sku');

        $first4Rows = $this->getDatagridData(ProductController::GRID_NAME, [], [], [
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
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param array $rows
     * @param string $sku
     * @return array|null
     */
    protected function getRowBySku(array $rows, $sku)
    {
        foreach ($rows as $row) {
            if ($row['sku'] === $sku) {
                return $row;
            }
        }

        return null;
    }
}
