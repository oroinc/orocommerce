<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionWithSortOrderData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductCollectionContentVariantDatagridListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
            LoadProductCollectionWithSortOrderData::class
        ]);
    }

    /**
     * @dataProvider requestAndResultDataProviderWithSegment
     *
     * @param array $request
     * @param array $expectedFilteredProducts
     */
    public function testOnBuildAfterWithSegment(array $request, array $expectedFilteredProducts): void
    {
        // convert reference names into ids & definitions
        $request = array_combine(
            array_keys($request),
            array_map(
                function ($key, $content) {
                    if ($content === LoadProductCollectionWithSortOrderData::SEGMENT) {
                        if (str_starts_with($key, 'si_')) {
                            $content = $this->getReference(LoadProductCollectionWithSortOrderData::SEGMENT)
                                ->getId();
                        }
                        if (str_starts_with($key, 'sd_')) {
                            $content = $this->getReference(LoadProductCollectionWithSortOrderData::SEGMENT)
                                ->getDefinition();
                        }
                    }
                    return $content;
                },
                array_keys($request),
                $request
            )
        );

        $this->client->request('GET', $this->getUrl('oro_datagrid_index', $request));
        $result = $this->client->getResponse();

        $data = json_decode($result->getContent(), true);
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

    public function requestAndResultDataProviderWithSegment(): array
    {
        $gridName = 'product-collection-content-variant-grid:scope_0';
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
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ]
            ],
            'validate sort order' => [
                'request' => [
                    'gridName' => $gridName,
                    'si_' . $gridName => LoadProductCollectionWithSortOrderData::SEGMENT,
                    'sd_' . $gridName => LoadProductCollectionWithSortOrderData::SEGMENT
                ],
                'expectedFilteredProducts' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_1
                ]
            ]
        ];
    }
}
