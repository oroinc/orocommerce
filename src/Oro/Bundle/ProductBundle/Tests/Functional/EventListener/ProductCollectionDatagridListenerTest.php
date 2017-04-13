<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductCollectionDatagridListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class
        ]);
    }

    public function testOnBuildAfter()
    {
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

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_index',
                ['gridName' => 'product-collection-grid']
            ),
            [
                'segmentDefinition' => $segmentDefinition
            ]
        );

        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);

        $filteredProducts = $data['data'];
        $this->assertCount(4, $filteredProducts);
        foreach ($filteredProducts as $productItem) {
            /** @var Product $product */
            $product = $this->getReference($productItem['sku']);
            $this->assertTrue($product->getFeatured());
        }
    }
}
