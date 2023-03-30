<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductKitTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
        ]);
    }

    public function testCreateProductKitWithEmptyProductKitItemsCollection(): void
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product_kit_with_empty_product_kit_items_collection.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not empty product kit items collection constraint',
                'detail' => 'Product kit should have at least one kit item fully specified.',
            ],
            $response
        );
    }

    public function testCreateProductKitWithKitItemOwnedByOtherKit(): void
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product_kit_with_kit_item_owned_by_other_kit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product kit items owned by product kit constraint',
                'detail' => 'Kit item "Product Kit 1 Item 1" cannot be used because it already '
                    . 'belongs to the product kit "PKSKU1".',
                "source" => [
                    "pointer" => "/data/relationships/kitItems/data/0",
                ],
            ],
            $response
        );
    }

    public function testCreateProductKit(): void
    {
        $response = $this->post(['entity' => 'products'], 'create_product_kit.yml');

        $responseContent = $this->updateResponseContent('create_product_kit.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testGetProductKitListFilteredBySku(): void
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['sku' => '@product_kit1->sku'], 'include' => 'kitItems']
        );

        $this->assertResponseContains('cget_product_kit_filter_by_sku.yml', $response);
    }

    public function testGetProductKit(): void
    {
        $response = $this->get(['entity' => 'products', 'id' => '@product_kit1->id']);

        $this->assertResponseContains('get_product_kit_by_id.yml', $response);
    }

    public function testTryToUpdateProductKitWithEmptyKitItems(): void
    {
        $content = $this->getRequestData('update_product_kit.yml');
        $content['data']['relationships']['kitItems'] = ['data' => []];
        unset($content['included']);

        $response = $this->patch(
            ['entity' => 'products', 'id' => '@product_kit1->id'],
            $content,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not empty product kit items collection constraint',
                'detail' => 'Product kit should have at least one kit item fully specified.',
            ],
            $response
        );
    }

    public function testUpdateProductKit(): void
    {
        $response = $this->patch(
            ['entity' => 'products', 'id' => '@product_kit1->id'],
            'update_product_kit.yml'
        );

        $newProductKitItemId = self::getNewResourceIdFromIncludedSection($response, 'productkititems3');
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $responseContent);
        self::assertArrayHasKey('relationships', $responseContent['data']);
        self::assertArrayHasKey('kitItems', $responseContent['data']['relationships']);
        self::assertArrayHasKey('data', $responseContent['data']['relationships']['kitItems']);
        self::assertTrue(count($responseContent['data']['relationships']['kitItems']['data']) > 1);
        self::assertArrayContains(
            [['type' => 'productkititems', 'id' => $newProductKitItemId]],
            $responseContent['data']['relationships']['kitItems']['data']
        );
    }

    public function testTryToUpdateKitItemRelationshipWithEmptyKitItems(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'products', 'id' => '<toString(@product_kit1->id)>', 'association' => 'kitItems'],
            [
                'data' => [],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not empty product kit items collection constraint',
                'detail' => 'Product kit should have at least one kit item fully specified.',
            ],
            $response
        );
    }

    public function testTryToDeleteKitItemRelationshipWhenItIsLastOne(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'products', 'id' => '<toString(@product_kit3->id)>', 'association' => 'kitItems'],
            [
                'data' => [
                    [
                        'type' => 'productkititems',
                        'id' => '<toString(@product_kit3_item1->id)>',
                    ],
                ],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not empty product kit items collection constraint',
                'detail' => 'Product kit should have at least one kit item fully specified.',
            ],
            $response
        );
    }

    public function testDeleteProductKit(): void
    {
        $productKit = $this->getReference('product_kit3');
        $productKitItem = $productKit->getKitItems()->first();

        $this->delete(['entity' => 'products', 'id' => (string)$productKit->getId()]);

        $this->assertNull(
            $this->getEntityManager()->find(Product::class, $productKit->getId())
        );

        $this->assertNull(
            $this->getEntityManager()->find(ProductKitItem::class, $productKitItem->getId())
        );
    }

    public function testTryToDeleteProductReferencedByProductKit(): void
    {
        $product = $this->getReference('product1');
        $id = $product->getId();

        $response = $this->delete(
            [
                'entity' => 'products',
                'id' => (string)$id,
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: Product "PSKU1" cannot be deleted '
                    . 'because it is used in the following product kits: '
                    . 'PKSKU2, PKSKU1.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );

        $this->assertNotNull($this->getEntityManager()->find(Product::class, $id));
    }

    public function testTryToDeleteReferencedProductUnitPrecisionRelationship(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'unitPrecisions'],
            [
                'data' => [
                    [
                        'type' => 'productunitprecisions',
                        'id' => '<toString(@product1_precision1->id)>',
                    ],
                ],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product unit precisions collection referenced by product kit items constraint',
                'detail' => 'Product unit "set" cannot be removed because it is used in the following '
                    . 'product kits: PKSKU2. Source: 1.',
            ],
            $response
        );
    }
}
