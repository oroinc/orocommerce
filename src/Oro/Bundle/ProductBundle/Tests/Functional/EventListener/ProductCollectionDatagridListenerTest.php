<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductCollectionDatagridListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @dataProvider requestAndResultDataProvider
     */
    public function testOnBuildAfter(array $request, array $expectedFilteredProducts)
    {
        // convert reference names array into string with Ids, in order to support exclude&include args
        $request = array_map(
            function ($content) {
                if (is_array($content)) {
                    $result = [];
                    foreach ($content as $productName) {
                        $result[] = $this->getReference($productName)->getId();
                    }
                    $content = implode(',', $result);
                }
                return $content;
            },
            $request
        );

        $this->client->request('GET', $this->getUrl('oro_datagrid_index', $request));
        $result = $this->client->getResponse();

        $data = self::jsonToArray($result->getContent());
        $filteredProductsSku = array_map(
            function (array $item) {
                return $item['sku'];
            },
            $data['data']
        );

        $expectedFilteredProductsSku = array_map(
            function ($productName) {
                return $this->getReference($productName)->getSku();
            },
            $expectedFilteredProducts
        );

        $this->assertCount(count($expectedFilteredProductsSku), $filteredProductsSku);
        $this->assertEquals($expectedFilteredProductsSku, $filteredProductsSku);
    }

    public function requestAndResultDataProvider(): array
    {
        $gridName = 'product-collection-grid:scope_0';
        $segmentDefinition = '{
            "columns":[{
                "name": "id",
                "label": "Id",
                "sorting": "",
                "func": null
             }],
            "filters":[{
                "columnName": "featured",
                "criterion": {
                    "filter": "boolean",
                    "data": {
                        "value": 1
                    }
                }
            }]
        }';

        return [
            'just definition' => [
                'request' => [
                    'gridName' => $gridName,
                    'sd_' . $gridName => $segmentDefinition
                ],
                'expectedFilteredProducts' => [
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_1,
                ]
            ],
            'definition with excluded&included' => [
                'request' => [
                    'gridName' => $gridName,
                    'sd_' . $gridName => $segmentDefinition,
                    'sd_' . $gridName . ':excl' => [LoadProductData::PRODUCT_1, LoadProductData::PRODUCT_2],
                    'sd_' . $gridName . ':incl' => [LoadProductData::PRODUCT_5],
                ],
                'expectedFilteredProducts' => [
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_3,
                ]
            ],
            'without definition just included' => [
                'request' => [
                    'gridName' => $gridName,
                    'sd_' . $gridName . ':incl' => [LoadProductData::PRODUCT_5],
                ],
                'expectedFilteredProductsSku' => [LoadProductData::PRODUCT_5]
            ],
            'empty definition and empty included&excluded' => [
                'request' => [
                    'gridName' => $gridName,
                    'sd_' . $gridName => '{}'
                ],
                'expectedFilteredProducts' => []
            ],
            'definition with just excluded' => [
                'request' => [
                    'gridName' => $gridName,
                    'sd_' . $gridName . ':excl' => [LoadProductData::PRODUCT_1],
                ],
                'expectedFilteredProducts' => []
            ],
        ];
    }
}
