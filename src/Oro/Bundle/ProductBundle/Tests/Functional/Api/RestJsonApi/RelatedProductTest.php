<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadUserData as CatalogLoadUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadRelatedProductData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class RelatedProductTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadRelatedProductData::class,
            LoadUserData::class,
            CatalogLoadUserData::class
        ]);
        $this->updateRolePermission(
            CatalogLoadUserData::ROLE_CATALOG_MANAGER,
            Product::class,
            AccessLevel::GLOBAL_LEVEL
        );
    }

    private function setRelatedProductsEnabled(bool $enabled): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.related_products_enabled', $enabled);
        $configManager->flush();
    }

    private function setRelatedProductsLimit(int $limit): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.max_number_of_related_products', $limit, 0);
        $configManager->set('oro_product.max_number_of_related_products', $limit, 1);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'relatedproducts']);

        $this->assertResponseContains('related_product/cget.yml', $response);
    }

    /**
     * oro_related_products_edit - false
     * Product::VIEW             - true
     */
    public function testTryToGetListWhenAccessDeniedOnLackOfPermissionToEditRelatedProducts(): void
    {
        $response = $this->cget(
            ['entity' => 'relatedproducts'],
            [],
            self::generateApiAuthHeader(LoadUserData::USER_NAME),
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to change related products.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * oro_related_products_edit - true
     * Product::VIEW             - false
     */
    public function testTryToGetListWhenAccessDeniedOnLackOfPermissionToViewProductEntity(): void
    {
        $this->updateRolePermission(
            CatalogLoadUserData::ROLE_CATALOG_MANAGER,
            Product::class,
            AccessLevel::NONE_LEVEL
        );

        $response = $this->cget(
            ['entity' => 'relatedproducts'],
            [],
            self::generateApiAuthHeader(CatalogLoadUserData::USER_NAME_CATALOG_MANAGER),
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGet(): void
    {
        $response = $this->get([
            'entity' => 'relatedproducts',
            'id' => '<toString(@related-product-product-3-product-1->id)>',
        ]);

        $this->assertResponseContains('related_product/get.yml', $response);
    }

    /**
     * oro_related_products_edit - false
     * Product::VIEW             - true
     */
    public function testTryToGetWhenAccessDeniedOnLackOfPermissionToEditRelatedProducts(): void
    {
        $response = $this->get(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-3-product-1->id)>',
            ],
            [],
            self::generateApiAuthHeader(LoadUserData::USER_NAME),
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to change related products.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * oro_related_products_edit - true
     * Product::VIEW             - false
     */
    public function testTryToGetAccessDeniedOnLackOfPermissionToViewProductEntity(): void
    {
        $this->updateRolePermission(
            CatalogLoadUserData::ROLE_CATALOG_MANAGER,
            Product::class,
            AccessLevel::NONE_LEVEL
        );

        $response = $this->get(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-3-product-1->id)>',
            ],
            [],
            self::generateApiAuthHeader(CatalogLoadUserData::USER_NAME_CATALOG_MANAGER),
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testCreate(): void
    {
        $this->setRelatedProductsEnabled(true);
        $this->setRelatedProductsLimit(100);

        $response = $this->post(['entity' => 'relatedproducts'], 'related_product/post.yml');

        $this->assertResponseContains('related_product/post.yml', $response);

        // test that the entity was created
        $entity = $this->getEntityManager()->find(RelatedProduct::class, $this->getResourceId($response));
        self::assertNotNull($entity);
    }

    public function testTryToCreateWhenFunctionalityIsDisabled(): void
    {
        $this->setRelatedProductsEnabled(false);

        $response = $this->post(['entity' => 'relatedproducts'], 'related_product/post.yml', [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'Related Items functionality is disabled.'
            ],
            $response
        );
    }

    public function testTryToCreateWhenUserTriesToAddProductToItself(): void
    {
        $this->setRelatedProductsEnabled(true);

        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post_relation_to_itself.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'It is not possible to create relations from product to itself.'
            ],
            $response
        );
    }

    public function testTryToCreateWhenRelationAlreadyExist(): void
    {
        $response = $this->post(['entity' => 'relatedproducts'], 'related_product/post_relation_exists.yml', [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'Relation between products already exists.'
            ],
            $response
        );
    }

    public function testTryToCreateWhenLimitExceeded(): void
    {
        $this->setRelatedProductsLimit(1);

        $response = $this->post(['entity' => 'relatedproducts'], 'related_product/post_limit.yml', [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'It is not possible to add more related items, because of the limit of relations.'
            ],
            $response
        );
    }

    public function testTryToCreateWhenRequestHasNoRelationshipData(): void
    {
        $this->setRelatedProductsLimit(10);

        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post_no_relationships.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/product/data']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/relatedItem/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenRequestHasNoProductRelationshipData(): void
    {
        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post_without_product.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWhenRequestHasNoRelatedItemRelationshipData(): void
    {
        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post_without_related_item.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/relatedItem/data']
            ],
            $response
        );
    }

    /**
     * oro_related_products_edit - false
     * Product::VIEW,EDIT        - true
     */
    public function testTryToCreateWhenAccessDeniedOnLackOfPermissionToEditRelatedProducts(): void
    {
        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post.yml',
            self::generateApiAuthHeader(LoadUserData::USER_NAME),
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to change related products.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * oro_related_products_edit - true
     * Product::VIEW             - true
     * Product::EDIT             - false
     */
    public function testTryToCreateWhenAccessDeniedOnLackOfPermissionToEditProductEntity(): void
    {
        $this->updateRolePermissionForAction(
            LoadRolesData::ROLE_USER,
            'oro_related_products_edit',
            true
        );

        $response = $this->post(
            ['entity' => 'relatedproducts'],
            'related_product/post.yml',
            self::generateApiAuthHeader(LoadUserData::USER_NAME_2),
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testDelete(): void
    {
        $this->delete(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-5-product-4->id)>',
            ]
        );

        $response = $this->get(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-5-product-4->id)>',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * oro_related_products_edit - true
     * Product::EDIT             - true
     * Product::VIEW             - false
     */
    public function testTryToDeleteWhenDeniedOnLackOfPermissionToViewProductEntity(): void
    {
        $this->updateRolePermission(
            CatalogLoadUserData::ROLE_CATALOG_MANAGER,
            Product::class,
            AccessLevel::GLOBAL_LEVEL,
            'EDIT'
        );
        $this->updateRolePermissionForAction(
            CatalogLoadUserData::ROLE_CATALOG_MANAGER,
            'oro_related_products_edit',
            true
        );

        $response = $this->delete(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-3-product-1->id)>',
            ],
            [],
            self::generateApiAuthHeader(CatalogLoadUserData::USER_NAME_CATALOG_MANAGER),
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);

        $response = $this->get(
            [
                'entity' => 'relatedproducts',
                'id' => '<toString(@related-product-product-3-product-1->id)>',
            ]
        );

        $this->assertResponseContains('related_product/get.yml', $response);
    }
}
