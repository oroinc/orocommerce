<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductSelectGridTest extends WebTestCase
{
    use MysqlVersionCheckTrait;

    const DATAGRID_NAME = 'products-select-grid-frontend';

    /**
     * @var Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadFrontendProductData::class
        ]);
        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
    }

    /**
     * @dataProvider sorterProvider
     */
    public function testGridCanBeSorted(array $sortBy, array $expectedResult)
    {
        $response = $this->client->requestFrontendGrid(['gridName' => self::DATAGRID_NAME], $sortBy, true);

        $result = $this->getJsonResponseContent($response, 200);

        $productNames = [];
        foreach ($result['data'] as $item) {
            $productNames[] = $item['sku'];
        }

        $this->assertSame($expectedResult, $productNames);
    }

    /**
     * @dataProvider filterProvider
     * @param array $filters
     * @param array $expectedResult
     * @param bool $isContains
     */
    public function testGridCanBeFiltered(array $filters, array $expectedResult, $isContains = false)
    {
        if ($isContains && $this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }
        $filters = array_merge($filters, [self::DATAGRID_NAME.'[_sort_by][sku]' => 'ASC']);
        $response = $this->client->requestFrontendGrid(['gridName' => self::DATAGRID_NAME], $filters, true);

        $result = $this->getJsonResponseContent($response, 200);

        $productNames = [];
        foreach ($result['data'] as $item) {
            $productNames[] = $item['sku'];
        }

        $this->assertSame($expectedResult, $productNames);
    }

    public function sorterProvider(): array
    {
        return [
            [
                [self::DATAGRID_NAME.'[_sort_by][productName]' => 'DESC'],
                array_reverse([
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9
                ])
            ],
            [
                [self::DATAGRID_NAME.'[_sort_by][productName]' => 'ASC'],
                [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9
                ]
            ],
            [
                [self::DATAGRID_NAME.'[_sort_by][sku]' => 'DESC'],
                array_reverse([
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9
                ])
            ],
            [
                [self::DATAGRID_NAME.'[_sort_by][sku]' => 'ASC'],
                [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_9
                ]
            ]
        ];
    }

    public function filterProvider(): array
    {
        return [
            [
                [
                    self::DATAGRID_NAME.'[_filter][productName][value]' => 'product',
                    self::DATAGRID_NAME.'[_filter][productName][type]' => TextFilterType::TYPE_CONTAINS,
                ],
                [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                ],
                true
            ],
            // Uncomment after fix BAP-16099.
//            [
//                [
//                    self::DATAGRID_NAME.'[_filter][productName][value]' => 'product-1.names',
//                    self::DATAGRID_NAME.'[_filter][productName][type]' => TextFilterType::TYPE_CONTAINS,
//                ],
//                [LoadProductData::PRODUCT_1],
//                false
//            ],
            [
                [self::DATAGRID_NAME.'[_filter][inventoryStatus][value][]' => 'out_of_stock'],
                [LoadProductData::PRODUCT_3],
                false
            ],
            [
                [
                    self::DATAGRID_NAME.'[_filter][sku][value]' => 'product-2',
                    self::DATAGRID_NAME.'[_filter][sku][type]' => TextFilterType::TYPE_EQUAL,
                ],
                [LoadProductData::PRODUCT_2],
                false
            ]
        ];
    }
}
