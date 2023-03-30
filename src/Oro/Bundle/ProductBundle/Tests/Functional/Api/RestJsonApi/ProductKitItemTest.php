<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductKitItemTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
        ]);
    }

    public function testCreateProductKitWithInvalidQuantity(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['data']['attributes']['minimumQuantity'] = 123.456;
        $content['data']['attributes']['maximumQuantity'] = 456.789;

        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseContainsValidationErrors(
            [
                [
                    'title' => 'product kit item quantity precision constraint',
                    'detail' => 'Minimum quantity 123.456 does not match the allowed product unit precision 1.',
                    'source' => ['pointer' => '/data/attributes/minimumQuantity'],
                ],
                [
                    'title' => 'product kit item quantity precision constraint',
                    'detail' => 'Maximum quantity 456.789 does not match the allowed product unit precision 1.',
                    'source' => ['pointer' => '/data/attributes/maximumQuantity'],
                ],
            ],
            $response
        );
    }

    public function testCreateProductKitNotSimpleProducts(): void
    {
        $response = $this->post(
            ['entity' => 'productkititems'],
            'create_product_kit_item_not_simple_products.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'only simple products in product kit item products collection constraint',
                'detail' => 'Only simple product can be used in kit options.',
                'source' => ['pointer' => '/data/relationships/kitItemProducts/data/0'],
            ],
            $response
        );
    }

    public function testCreateProductKitUnitNotAvailableForAllSpecifiedProducts(): void
    {
        $response = $this->post(
            ['entity' => 'productkititems'],
            'create_product_kit_item_unit_not_available_for_all_specified_products.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'product kit item unit available for specified products constraint',
                    'detail' => 'Unit of quantity should be available for all specified products.',
                    'source' => ['pointer' => '/data/relationships/productUnit/data'],
                ],
            ],
            $response
        );
    }

    public function testCreateProductKitItemProductUnitCanNotBeEmpty(): void
    {
        $content = $this->getRequestData('create_product_kit_item_unit_can_not_be_empty.yml');
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'Unit of quantity cannot be empty.',
                'source' => ['pointer' => '/data/relationships/productUnit/data'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemWithInvalidProductKit(): void
    {
        $content = $this->getRequestData('create_product_kit_item_with_invalid_product_kit.yml');
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'product type constraint',
                'detail' => 'Type "simple" is not allowed. The only allowed type is "kit".',
                'source' => ['pointer' => '/data/relationships/productKit/data/type'],
            ],
            $response
        );
    }

    public function testCreateProductKitItem(): void
    {
        $response = $this->post(
            ['entity' => 'productkititems'],
            'create_product_kit_item.yml'
        );

        $responseContent = $this->updateResponseContent('create_product_kit_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateProductKitItemWithoutLabels(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        unset($content['data']['relationships']['labels'], $content['included'][0]);
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/relationships/labels/data'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemWhenTooLongLabel(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['included'][0]['attributes']['string'] = str_pad('a', 300, 'a');
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'length constraint',
                'detail' => 'This value is too long. It should have 255 characters or less.',
                'source' => ['pointer' => '/included/0/attributes/string'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemWhenInvalidLabelFallback(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['included'][0]['attributes']['fallback'] = 'invalid_fallback';
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/included/0/attributes/fallback'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemWithoutProductKit(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        unset($content['data']['relationships']['productKit']);
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/productKit/data'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemWithoutKitItemProducts(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        unset($content['data']['relationships']['kitItemProducts']);
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'Each kit option should have at least one product specified.',
                'source' => ['pointer' => '/data/relationships/kitItemProducts/data'],
            ],
            $response
        );
    }

    public function testCreateProductKitItemSetDefaultProductUnitFromProductKit(): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        unset($content['data']['relationships']['productUnit']);
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $responseContent = $this->updateResponseContent('create_product_kit_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    /**
     * @dataProvider invalidSortOrderDataProvider
     */
    public function testCreateProductKitItemWhenInvalidSortOrder(mixed $value): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['data']['attributes']['sortOrder'] = $value;
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/sortOrder'],
            ],
            $response
        );
    }

    public function invalidSortOrderDataProvider(): array
    {
        return [['string'], [42.42]];
    }

    /**
     * @dataProvider invalidQuantityDataProvider
     */
    public function testCreateProductKitItemWhenInvalidMinimumQuantity(
        mixed $value,
        string $title,
        string $detail
    ): void {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['data']['attributes']['minimumQuantity'] = $value;
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => $title,
                'detail' => $detail,
                'source' => ['pointer' => '/data/attributes/minimumQuantity'],
            ],
            $response
        );
    }

    public function invalidQuantityDataProvider(): array
    {
        return [
            ['value' => 'string', 'title' => 'form constraint', 'detail' => 'This value is not valid.'],
            [
                'value' => -42,
                'title' => 'range constraint',
                'detail' => 'This value should be 0 or more.',
            ],
        ];
    }

    /**
     * @dataProvider invalidQuantityDataProvider
     */
    public function testCreateProductKitItemWhenInvalidMaximumQuantity(
        mixed $value,
        string $title,
        string $detail
    ): void {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['data']['attributes']['maximumQuantity'] = $value;
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => $title,
                'detail' => $detail,
                'source' => ['pointer' => '/data/attributes/maximumQuantity'],
            ],
            $response
        );
    }

    /**
     * @dataProvider invalidOptionalDataProvider
     */
    public function testCreateProductKitItemWhenInvalidOptional(mixed $value): void
    {
        $content = $this->getRequestData('create_product_kit_item.yml');
        $content['data']['attributes']['optional'] = $value;
        $response = $this->post(['entity' => 'productkititems'], $content, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/optional'],
            ],
            $response
        );
    }

    public function invalidOptionalDataProvider(): array
    {
        return [['string'], [42]];
    }

    public function testGetProductKitItemListFilteredById(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititems'],
            ['filter' => ['id' => '@product_kit1_item1->id'], 'include' => 'labels']
        );

        $this->assertResponseContains('cget_product_kit_item_filter_by_id.yml', $response);
    }

    public function testGetProductKitItem(): void
    {
        $response = $this->get(
            ['entity' => 'productkititems', 'id' => '@product_kit1_item1->id'],
            ['include' => 'labels']
        );

        $this->assertResponseContains('get_product_kit_item_by_id.yml', $response);
    }

    public function testUpdateProductKitItemTryToChangeProductKit(): void
    {
        $response = $this->patch(
            ['entity' => 'productkititems', 'id' => '@product_kit1_item2->id'],
            'update_product_kit_item_change_product_kit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'unchangeable field constraint',
                'detail' => 'Field cannot be changed once set',
            ],
            $response
        );
    }

    public function testUpdateProductKitItemWithEmptyKitItemProducts(): void
    {
        $content = $this->getRequestData('update_product_kit_item.yml');
        $content['data']['relationships']['kitItemProducts'] = ['data' => []];

        $response = $this->patch(
            ['entity' => 'productkititems', 'id' => '@product_kit1_item2->id'],
            $content,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'Each kit option should have at least one product specified.',
            ],
            $response
        );
    }

    public function testUpdateProductKitItem(): void
    {
        $response = $this->patch(
            ['entity' => 'productkititems', 'id' => '@product_kit1_item2->id'],
            'update_product_kit_item.yml'
        );

        $responseContent = $this->updateResponseContent('update_product_kit_item.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testDeleteProductKitItem(): void
    {
        /** @var Product $product */
        $product = $this->getReference('product_kit1');
        $productKitItem = $product->getKitItems()->first();

        $this->delete(['entity' => 'productkititems', 'id' => (string)$productKitItem->getId()]);

        $this->assertCount(
            $product->getKitItems()->count() - 1,
            $this->getEntityManager()->getRepository(ProductKitItem::class)->findBy(['productKit' => $product->getId()])
        );

        $this->assertNull(
            $this->getEntityManager()->find(ProductKitItem::class, $productKitItem->getId())
        );
    }

    public function testTryToUpdateKitItemProductsRelationshipWithEmptyCollection(): void
    {
        /** @var Product $product */
        $productKit = $this->getReference('product_kit3');
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKit->getKitItems()->first();

        $response = $this->patchRelationship(
            ['entity' => 'productkititems', 'id' => $productKitItem->getId(), 'association' => 'kitItemProducts'],
            [
                'data' => [],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'Each kit option should have at least one product specified.',
            ],
            $response
        );
    }

    public function testTryDeleteKitItemProductRelationshipWhenItIsLast(): void
    {
        /** @var Product $product */
        $productKit = $this->getReference('product_kit3');
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKit->getKitItems()->first();
        $kitItemProduct = $productKitItem->getKitItemProducts()->first();

        $response = $this->deleteRelationship(
            ['entity' => 'productkititems', 'id' => $productKitItem->getId(), 'association' => 'kitItemProducts'],
            [
                'data' => [
                    [
                        'type' => 'productkititemproducts',
                        'id' => $kitItemProduct->getId(),
                    ],
                ],
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'Each kit option should have at least one product specified.',
            ],
            $response
        );
    }
}
