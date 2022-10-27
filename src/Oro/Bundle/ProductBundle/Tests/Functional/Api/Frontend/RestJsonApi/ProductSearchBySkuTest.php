<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM\PreviouslyPurchasedFeatureTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

/**
 * Tests "productsearch" API resource when SKU is used as the product identifier.
 */
class ProductSearchBySkuTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;
    use PreviouslyPurchasedFeatureTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml'
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->enablePreviouslyPurchasedFeature();
        $this->reindexProductData();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['page[size]' => 1, 'sort' => 'id'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains('cget_product_search_by_sku.yml', $response);
    }

    public function testIncludeProduct()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['include' => 'product', 'page[size]' => 1, 'sort' => 'id'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => 'productsearch',
                        'id'            => '<toString(@product1->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id'   => '@product1->sku'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id'   => '@product1->sku'
                    ]
                ]
            ],
            $response
        );
    }
}
