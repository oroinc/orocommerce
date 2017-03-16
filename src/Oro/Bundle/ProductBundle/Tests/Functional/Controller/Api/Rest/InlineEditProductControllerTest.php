<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class InlineEditProductControllerTest extends WebTestCase
{
    const NEW_PRODUCT_NAME = 'New default product-1 name';
    const NEW_INVENTORY_STATUS_ID = 'out_of_stock';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testProductEditName()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals(LoadProductData::PRODUCT_1_DEFAULT_NAME, $product1->getName());

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $product1->getId()]),
            [
                'productName' => self::NEW_PRODUCT_NAME
            ]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $this->assertEquals(self::NEW_PRODUCT_NAME, $product1->getName());
    }

    public function testProductEditNameMissingProduct()
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $id = $product8->getId() + 999999;

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $id]),
            [
                'productName' => self::NEW_PRODUCT_NAME
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

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_inline_edit_name', ['id' => $id])
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testProductEditInventoryStatus()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $product1->getInventoryStatus()->getId());

        $this->client->request(
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

        $this->client->request(
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

        $this->client->request(
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

        $this->client->request(
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
