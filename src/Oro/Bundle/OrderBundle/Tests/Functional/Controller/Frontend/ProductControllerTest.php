<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

/**
 * @dbIsolationPerTest
 */
class ProductControllerTest extends FrontendWebTestCase
{
    use WebsiteSearchExtensionTrait;

    const FRONTEND_GRID_NAME = 'order-products-previously-purchased-grid';

    /** {@inheritdoc} */
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadOrders::class,
                LoadOrderLineItemData::class,
            ]
        );

        $this->reindexProductData();
    }

    public function testPreviouslyPurchasedGrid()
    {
        $response = $this->client->requestFrontendGrid(self::FRONTEND_GRID_NAME, [], true);

        $result = static::getJsonResponseContent($response, 200);

        $this->assertCount(2, $result['data']);

        $productData = $result['data'];

        $this->assertEquals(LoadProductData::PRODUCT_1, $productData[0]['sku']);
        $this->assertEquals(LoadProductData::PRODUCT_6, $productData[1]['sku']);
    }

    public function testPreviouslyPurchasedGridIfUserNonAuth()
    {
        /** login as anonymous user */
        $this->initClient([]);

        $response = $this->client->requestFrontendGrid(self::FRONTEND_GRID_NAME, [], true);

        $this->assertResponseStatusCodeEquals(
            $response,
            401,
            sprintf('Please check acl for "%s"', self::FRONTEND_GRID_NAME)
        );
    }

    /**
     * @param array $gridParam
     * @param array $expected
     *
     * @dataProvider gridParamDataProvider
     */
    public function testSortPreviouslyPurchasedGrid(array $gridParam, array $expected)
    {
        $response = $this->client->requestFrontendGrid(self::FRONTEND_GRID_NAME, $gridParam, true);

        $result = static::getJsonResponseContent($response, 200);

        $this->assertCount(count($expected), $result['data']);

        foreach ($expected as $key => $item) {
            $this->assertEquals($item, $result['data'][$key]['sku']);
        }
    }

    public function gridParamDataProvider()
    {
        return [
            'With sort by name desk' => [
                [
                    self::FRONTEND_GRID_NAME . '[_sort_by][names]' => 'DESC'
                ],
                [
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_1,
                ]
            ],
            'With sort by name ask' => [
                [
                    self::FRONTEND_GRID_NAME . '[_sort_by][names]' => 'ASK'
                ],
                [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_6,
                ]
            ],
            'With filter by sku case_1' => [
                [
                    self::FRONTEND_GRID_NAME . '[_filter][sku][type]'  => NumberFilterTypeInterface::TYPE_EQUAL,
                    self::FRONTEND_GRID_NAME . '[_filter][sku][value]' => LoadProductData::PRODUCT_6,
                ],
                [
                    LoadProductData::PRODUCT_6
                ]
            ],
            'With filter by sku case_2' => [
                [
                    self::FRONTEND_GRID_NAME . '[_filter][sku][type]'  => NumberFilterTypeInterface::TYPE_EQUAL,
                    self::FRONTEND_GRID_NAME . '[_filter][sku][value]' => LoadProductData::PRODUCT_1,
                ],
                [
                    LoadProductData::PRODUCT_1
                ]
            ],
        ];
    }
}
