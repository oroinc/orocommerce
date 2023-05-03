<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM\PreviouslyPurchasedFeatureTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

/**
 * @dbIsolationPerTest
 * @property \Oro\Bundle\FrontendTestFrameworkBundle\Test\Client $client
 */
class ProductControllerTest extends FrontendWebTestCase
{
    use WebsiteSearchExtensionTrait;
    use PreviouslyPurchasedFeatureTrait;

    private const FRONTEND_GRID_NAME = 'order-products-previously-purchased-grid';

    protected function setUp(): void
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

        $this->enablePreviouslyPurchasedFeature();

        $this->reindexProductData();
    }

    public function testPreviouslyPurchasedGrid()
    {
        $response = $this->client->requestFrontendGrid(self::FRONTEND_GRID_NAME, [], true);

        $result = self::getJsonResponseContent($response, 200);

        $this->assertCount(2, $result['data']);

        $productData = $result['data'];

        $skus = array_flip(array_column($productData, 'sku'));
        $this->assertArrayHasKey(LoadProductData::PRODUCT_1, $skus);
        $this->assertArrayHasKey(LoadProductData::PRODUCT_6, $skus);
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
     * @dataProvider gridParamDataProvider
     */
    public function testSortPreviouslyPurchasedGrid(array $gridParam, array $expected)
    {
        $this->markTestSkipped('will be unskipped in BB-13532');

        $response = $this->client->requestFrontendGrid(self::FRONTEND_GRID_NAME, $gridParam, true);

        $result = self::getJsonResponseContent($response, 200);

        $this->assertCount(count($expected), $result['data']);

        foreach ($expected as $key => $item) {
            $this->assertEquals($item, $result['data'][$key]['sku']);
        }
    }

    public function gridParamDataProvider(): array
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
                    self::FRONTEND_GRID_NAME . '[_sort_by][names]' => 'ASC'
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
