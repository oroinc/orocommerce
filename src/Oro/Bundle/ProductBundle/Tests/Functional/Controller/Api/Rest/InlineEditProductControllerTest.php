<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class InlineEditProductControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const NEW_PRODUCT_NAME = 'New default product-1 name';
    private const NEW_PRODUCT_SLUG_PROTOTYPE = 'new-default-product-1-name';
    private const NEW_INVENTORY_STATUS_ID = 'out_of_stock';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadProductData::class]);
    }

    public function productEditNameRedirectDataProvider(): array
    {
        return [
            'Redirect strategy empty, create redirect false' => [
                'redirectStrategy' => null,
                'createRedirect' => false,
                'expectedCreateRedirect' => false,
            ],
            'Redirect strategy ask, create redirect false' => [
                'redirectStrategy' => Configuration::STRATEGY_ASK,
                'createRedirect' => false,
                'expectedCreateRedirect' => false,
            ],
            'Redirect strategy never, create redirect false' => [
                'redirectStrategy' => Configuration::STRATEGY_NEVER,
                'createRedirect' => false,
                'expectedCreateRedirect' => false,
            ],
            'Redirect strategy always, create redirect false' => [
                'redirectStrategy' => Configuration::STRATEGY_ALWAYS,
                'createRedirect' => false,
                'expectedCreateRedirect' => true,
            ],
            'Redirect strategy empty, create redirect true' => [
                'redirectStrategy' => null,
                'createRedirect' => true,
                'expectedCreateRedirect' => true,
            ],
            'Redirect strategy ask, create redirect true' => [
                'redirectStrategy' => Configuration::STRATEGY_ASK,
                'createRedirect' => true,
                'expectedCreateRedirect' => true,
            ],
            'Redirect strategy never, create redirect true' => [
                'redirectStrategy' => Configuration::STRATEGY_NEVER,
                'createRedirect' => true,
                'expectedCreateRedirect' => false,
            ],
            'Redirect strategy always, create redirect true' => [
                'redirectStrategy' => Configuration::STRATEGY_ALWAYS,
                'createRedirect' => true,
                'expectedCreateRedirect' => true,
            ],
        ];
    }

    /**
     * @dataProvider productEditNameRedirectDataProvider
     */
    public function testProductEditName(?string $redirectStrategy, bool $createRedirect, bool $expectedCreateRedirect)
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());
        $this->assertEquals(LoadProductData::PRODUCT_1_DEFAULT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        $this->assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());

        $configManager = self::getConfigManager();
        $configManager->set('oro_redirect.redirect_generation_strategy', $redirectStrategy);
        $configManager->flush();
        $configManager->reload();

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
            [
                'productName' => self::NEW_PRODUCT_NAME,
                'createRedirect' => $createRedirect,
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
        $this->assertEquals(self::NEW_PRODUCT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        $this->assertEquals($expectedCreateRedirect, $product1->getSlugPrototypesWithRedirect()->getCreateRedirect());
    }

    public function testProductEditNameMissingProduct()
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $id]),
            [
                'productName' => self::NEW_PRODUCT_NAME,
                'createRedirect' => false,
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditNameMissingProductName()
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $id])
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditNameMissingCreateRedirect()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());
        $this->assertEquals(LoadProductData::PRODUCT_1_DEFAULT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        $this->assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
            [
                'productName' => self::NEW_PRODUCT_NAME,
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
        $this->assertEquals(self::NEW_PRODUCT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        $this->assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());
    }

    public function testProductEditInventoryStatus()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $product1->getInventoryStatus()->getId());

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()]),
            [
                'inventoryStatusId' => self::NEW_INVENTORY_STATUS_ID
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(self::NEW_INVENTORY_STATUS_ID, $product1->getInventoryStatus()->getId());
    }

    public function testProductEditInventoryStatusEmptyParameters()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 400);
    }

    public function testProductEditInventoryStatusMissingProduct()
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $id])
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditUnknownInventoryStatus()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()]),
            [
                'inventoryStatusId' => 'unknown_inventory_status',
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
