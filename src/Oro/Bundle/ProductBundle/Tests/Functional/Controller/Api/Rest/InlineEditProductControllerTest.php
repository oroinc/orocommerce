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

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
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
    public function testProductEditName(
        ?string $redirectStrategy,
        bool $createRedirect,
        bool $expectedCreateRedirect
    ): void {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());

        $configManager = self::getConfigManager();
        $initialStrategy = $configManager->get('oro_redirect.redirect_generation_strategy');
        $configManager->set('oro_redirect.redirect_generation_strategy', $redirectStrategy);
        $configManager->flush();
        try {
            $this->client->jsonRequest(
                'PATCH',
                $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
                ['productName' => self::NEW_PRODUCT_NAME, 'createRedirect' => $createRedirect]
            );
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_redirect.redirect_generation_strategy', $initialStrategy);
            $configManager->flush();
        }

        self::assertEquals(['productName' => self::NEW_PRODUCT_NAME], self::getJsonResponseContent($result, 200));
        self::assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
        self::assertEquals(self::NEW_PRODUCT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertEquals($expectedCreateRedirect, $product1->getSlugPrototypesWithRedirect()->getCreateRedirect());
    }

    public function testProductEditNameWhenContainsTags(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());

        $configManager = self::getConfigManager();
        $initialStrategy = $configManager->get('oro_redirect.redirect_generation_strategy');
        $configManager->set('oro_redirect.redirect_generation_strategy', Configuration::STRATEGY_ASK);
        $configManager->flush();
        try {
            $this->client->jsonRequest(
                'PATCH',
                $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
                [
                    'productName' => '<a href="#">' . self::NEW_PRODUCT_NAME . '</a>',
                    'createRedirect' => true,
                ]
            );
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_redirect.redirect_generation_strategy', $initialStrategy);
            $configManager->flush();
        }

        self::assertEquals(['productName' => self::NEW_PRODUCT_NAME], self::getJsonResponseContent($result, 200));
        self::assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
        self::assertEquals(self::NEW_PRODUCT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());
    }

    public function testProductEditNameMissingProduct(): void
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

        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditNameMissingProductName(): void
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $id])
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditNameMissingCreateRedirect(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());
        self::assertEquals(LoadProductData::PRODUCT_1_DEFAULT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
            [
                'productName' => self::NEW_PRODUCT_NAME,
            ]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        self::assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
        self::assertEquals(self::NEW_PRODUCT_SLUG_PROTOTYPE, $product1->getDefaultSlugPrototype());
        self::assertTrue($product1->getSlugPrototypesWithRedirect()->getCreateRedirect());
    }

    public function testProductEditInventoryStatus(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        self::assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $product1->getInventoryStatus()->getInternalId());

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()]),
            [
                'inventory_status' => self::NEW_INVENTORY_STATUS_ID
            ]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        self::assertEquals(self::NEW_INVENTORY_STATUS_ID, $product1->getInventoryStatus()->getInternalId());
    }

    public function testProductEditInventoryStatusEmptyParameters(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()])
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 400);
    }

    public function testProductEditInventoryStatusMissingProduct(): void
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $id])
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditUnknownInventoryStatus(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);

        $this->client->jsonRequest(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_inventory_status', ['id' => $product1->getId()]),
            [
                'inventory_status' => 'unknown_inventory_status',
            ]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 404);
    }
}
