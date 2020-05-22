<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadVariantFields;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolationPerTest
 */
class ProductUpdateListTest extends RestJsonApiUpdateListTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadProductUnits::class,
            LoadProductUnitPrecisions::class,
            LoadBusinessUnitData::class,
            LoadOrganizations::class,
            LoadProductTaxCodes::class,
            LoadCategoryData::class,
            LoadVariantFields::class,
            LoadLocalizationData::class,
            LoadProductData::class
        ]);
    }

    public function testCreateSimpleProducts()
    {
        $this->processUpdateList(
            Product::class,
            'create_product_list.yml'
        );

        $response = $this->cget(['entity' => 'products'], ['filter[id][gt]' => '@продукт-9->id']);

        $repo = $this->getEntityManager()->getRepository(Product::class);
        /** @var Product $product1 */
        $product1 = $repo->findOneBySku('test-api-01');
        self::assertEquals('Test product 1', $product1->getName());
        self::assertEquals('Test product 1 es', $product1->getName($this->getReference('es')));

        /** @var Product $product2 */
        $product2 = $repo->findOneBySku('test-api-02');
        self::assertEquals('Test product 2', $product2->getName());

        $responseContent = $this->updateResponseContent('create_product_list.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateProducts()
    {
        $this->processUpdateList(
            Product::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'products',
                        'id'         => '<toString(@product-1->id)>',
                        'attributes' => ['status' => 'disabled']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'products',
                        'id'         => '<toString(@product-2->id)>',
                        'attributes' => ['status' => 'disabled']
                    ]
                ]
            ]
        );

        $response = $this->cget(
            ['entity' => 'products'],
            [
                'filter' => ['sku' => ['@product-1->sku', '@product-2->sku']],
                'fields[products]' => 'status'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'products',
                        'id'         => '<toString(@product-1->id)>',
                        'attributes' => ['status' => 'disabled']
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '<toString(@product-2->id)>',
                        'attributes' => ['status' => 'disabled']
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateConfigurableProductViaUpdateListRequest()
    {
        $this->processUpdateList(
            Product::class,
            'create_configurable_list.yml'
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBySku('configurable-test-api-1');

        self::assertEquals('Test product', $product->getName());
        self::assertEquals('configurable', $product->getType());
        self::assertEquals('enabled', $product->getStatus());
        self::assertEquals(['test_variant_field'], $product->getVariantFields());

        $variantLinks = $product->getVariantLinks();
        self::assertEquals(1, $variantLinks->count());

        /** @var ProductVariantLink $variantLink */
        $variantLink = $variantLinks->first();
        $variantProduct = $variantLink->getProduct();
        self::assertEquals('Test variant product', $variantProduct->getName());
        self::assertEquals('simple', $variantProduct->getType());
        self::assertEquals('enabled', $variantProduct->getStatus());
    }
}
