<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

/**
 * Tests "productsearch" API resource when SKU is used as the product identifier.
 */
class ProductSearchBySkuTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_prices.yml'
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', true);
        $configManager->flush();

        self::reindexProductData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', false);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['page[size]' => 1, 'sort' => 'id'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains('cget_product_search_by_sku.yml', $response);
    }

    public function testIncludeProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['include' => 'product', 'page[size]' => 1, 'sort' => 'id'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id' => '@product1->sku'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '@product1->sku'
                    ]
                ]
            ],
            $response
        );
    }
}
