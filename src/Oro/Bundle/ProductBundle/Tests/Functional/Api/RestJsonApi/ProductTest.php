<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadVariantFields;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
            LoadWorkflowDefinitions::class,
        ]);
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product.yml'
        );

        $productId = $this->getResourceId($response);
        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $productId);

        $localizations = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(Localization::class)
            ->findAll();

        // + 1 because we also have the default one without localization
        $localizationsNumber = count($localizations) + 1;
        $this->assertCount($localizationsNumber, $product->getNames());
        $this->assertCount($localizationsNumber, $product->getDescriptions());
        $this->assertCount($localizationsNumber, $product->getShortDescriptions());
        $this->assertCount($localizationsNumber, $product->getMetaDescriptions());
        $this->assertCount($localizationsNumber, $product->getMetaKeywords());
        $this->assertCount($localizationsNumber, $product->getMetaTitles());
        $this->assertCount($localizationsNumber, $product->getNames());

        $this->assertEquals('test-api-2', $product->getSku());
        $this->assertEquals('enabled', $product->getStatus());
        $this->assertEquals('simple', $product->getType());
        $this->assertEquals(true, $product->getFeatured());
        $this->assertEquals(false, $product->isNewArrival());
    }

    public function testGetListFilteredByProduct()
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['sku' => '@product-1->sku']]
        );

        $this->assertResponseContains('cget_filter_by_product.yml', $response);
    }

    public function testGetListFilteredBySeveralProductsWithDifferentInventoryStatuses()
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['sku' => ['@product-2->sku', '@product-3->sku']]]
        );

        $this->assertResponseContains('cget_filter_by_products_by_inventory_status.yml', $response);
    }

    public function testGet()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $response = $this->get(
            ['entity' => 'products', 'id' => $product->getId()]
        );

        $this->assertResponseContains('get_product_by_id.yml', $response);
    }

    public function testUpdate()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product.yml'
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());
        $referenceRepository = $this->getReferenceRepository();

        foreach ($product->getNames() as $name) {
            $localization = $name->getLocalization() === null ?
                'default'
                : $name->getLocalization()->getFormattingCode();
            $reference = LoadProductData::PRODUCT_1 . '.names.' . $localization;
            if (!$referenceRepository->hasReference($reference)) {
                $referenceRepository->addReference($reference, $name);
            }
        }

        $defaultName = $product->getName(null);
        $defaultDescription = $product->getDescription(null);
        $newUnitPrecision = $product->getUnitPrecision('box');
        $bottlePrecision = $product->getUnitPrecision('bottle');

        $this->assertEquals('Test product changed', $defaultName->getString());
        $this->assertEquals('<b>Description Bold</b>', $defaultDescription->getText());
        $this->assertInstanceOf(ProductUnitPrecision::class, $newUnitPrecision);
        $this->assertEquals('15', $newUnitPrecision->getConversionRate());
        $this->assertEquals('99', $bottlePrecision->getConversionRate());

        $referenceRepository->setReference(
            'product_unit_precision.' . LoadProductData::PRODUCT_1 . '.box',
            $newUnitPrecision
        );
        $referenceRepository->setReference(LoadProductData::PRODUCT_1, $product);

        $this->assertResponseContains('patch_update_entity.yml', $response);
    }

    public function testCreateWithPageTemplateScalarValue()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['attributes']['sku'] = 'test-api-pt-scalar';
        $data['data']['relationships']['pageTemplate']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $data['included'][] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template',
            'attributes' => [
                'fallback'    => null,
                'scalarValue' => 'short',
                'arrayValue'  => null
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data);

        /** @var Product $product */
        $product = $this->getEntityManager()
            ->find(Product::class, $this->getResourceId($response));

        $pageTemplate = $product->getPageTemplate();
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $pageTemplate);
        $this->assertArrayHasKey(ProductType::PAGE_TEMPLATE_ROUTE_NAME, $pageTemplate->getOwnValue());
        $this->assertEquals('short', $pageTemplate->getArrayValue()[ProductType::PAGE_TEMPLATE_ROUTE_NAME]);
        $this->assertNull($pageTemplate->getScalarValue());
        $this->assertNull($pageTemplate->getFallback());
    }

    public function testCreateWithPageTemplateFallbackToSystemConfig()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['attributes']['sku'] = 'test-api-pt-sys-conf';
        $data['data']['relationships']['pageTemplate']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $data['included'][] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template',
            'attributes' => [
                'fallback'    => 'systemConfig',
                'scalarValue' => null,
                'arrayValue'  => null
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data);

        /** @var Product $product */
        $product = $this->getEntityManager()
            ->find(Product::class, $this->getResourceId($response));

        $pageTemplate = $product->getPageTemplate();
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $pageTemplate);
        $this->assertEquals('systemConfig', $pageTemplate->getFallback());
        $this->assertNull($pageTemplate->getScalarValue());
    }

    public function testCreateWithInvalidPageTemplateBecauseOfInvalidValue()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['pageTemplate']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $data['included'][] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template',
            'attributes' => [
                'scalarValue' => 'invalid-value'
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'product page template constraint',
                'detail' => 'The selected value is not valid.',
                'source' => ['pointer' => '/data/relationships/pageTemplate/data']
            ],
            $response
        );
    }

    public function testCreateWithInvalidPageTemplateBecauseOfFallbackValueIsEmpty()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['pageTemplate']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $data['included'][] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'entity field fallback value constraint',
                'detail' => 'Either "fallback", "scalarValue" or "arrayValue" property should be specified.',
                'source' => ['pointer' => sprintf('/included/%d', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithInvalidPageTemplateBecauseOfFallbackValueHasInvalidFallback()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['pageTemplate']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'page-template'
        ];
        $data['included'][] = [
            'type'       => 'entityfieldfallbackvalues',
            'id'         => 'page-template',
            'attributes' => [
                'fallback' => 'invalid-fallback'
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value is not valid. Acceptable values: systemConfig.',
                'source' => ['pointer' => sprintf('/included/%d/attributes/fallback', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithInvalidIsUpcomingBecauseOfFallbackValueIsEmpty()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['isUpcoming']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'is-upcoming'
        ];
        $data['included'][] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'is-upcoming'
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'entity field fallback value constraint',
                'detail' => 'Either "fallback", "scalarValue" or "arrayValue" property should be specified.',
                'source' => ['pointer' => sprintf('/included/%d', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithInvalidIsUpcomingBecauseOfFallbackValueHasMoreThanOneAttribute()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['isUpcoming']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'is-upcoming'
        ];
        $data['included'][] = [
            'type'       => 'entityfieldfallbackvalues',
            'id'         => 'is-upcoming',
            'attributes' => [
                'scalarValue' => 'test',
                'arrayValue'  => ['key' => 'value']
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'entity field fallback value constraint',
                'detail' => 'Either "fallback", "scalarValue" or "arrayValue" property should be specified.',
                'source' => ['pointer' => sprintf('/included/%d', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithInvalidIsUpcomingBecauseOfFallbackValueHasInvalidFallback()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['isUpcoming']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'is-upcoming'
        ];
        $data['included'][] = [
            'type'       => 'entityfieldfallbackvalues',
            'id'         => 'is-upcoming',
            'attributes' => [
                'fallback' => 'invalid-fallback'
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value is not valid. Acceptable values: category.',
                'source' => ['pointer' => sprintf('/included/%d/attributes/fallback', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithInvalidIsUpcomingBecauseOfFallbackValueIsArrayValueInsteadOfScalarValue()
    {
        $data = $this->getRequestData('create_product_min.yml');
        $data['data']['relationships']['isUpcoming']['data'] = [
            'type' => 'entityfieldfallbackvalues',
            'id'   => 'is-upcoming'
        ];
        $data['included'][] = [
            'type'       => 'entityfieldfallbackvalues',
            'id'         => 'is-upcoming',
            'attributes' => [
                'arrayValue' => ['key' => 'value']
            ]
        ];
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not null constraint',
                'detail' => 'The value should not be null.',
                'source' => ['pointer' => sprintf('/included/%d/attributes/scalarValue', count($data['included']) - 1)]
            ],
            $response
        );
    }

    public function testCreateWithEmptyNames()
    {
        $data = $this->getRequestData('create_product_min.yml');
        unset($data['data']['relationships']['names']);
        unset($data['included'][0]);
        $response = $this->post(['entity' => 'products'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/relationships/names/data']
            ],
            $response
        );
    }

    public function testCreateWithInvalidProductUnit()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product_invalid_product_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/primaryUnitPrecision/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'This value is not valid.',
                    'source' => ['pointer' => '/included/1/relationships/unit/data']
                ]
            ],
            $response
        );
    }

    public function testCreateWithDuplicateUnitPrecision()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product_duplicate_unit_precision.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Unit precision "bottle" already exists for this product.',
                'source' => ['pointer' => '/data/relationships/unitPrecisions/data']
            ],
            $response
        );
    }

    public function testUpdateWithInvalidProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_invalid_product_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/included/0/relationships/unit/data']
            ],
            $response
        );
    }

    public function testUpdateWhenUnitForNewUnitPrecisionIsNotProvided()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_no_product_unit.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'Unit should not be blank.',
                'source' => ['pointer' => '/included/0/relationships/unit/data']
            ],
            $response
        );
    }

    public function testUpdateWithDuplicateUnitPrecision()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_duplicate_unit_precision.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Unit precision "bottle" already exists for this product.',
                'source' => ['pointer' => '/data/relationships/unitPrecisions/data']
            ],
            $response
        );
    }

    public function testUpdateWithDuplicateExistingUnitPrecision()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_duplicate_existing_unit_precision.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'form constraint',
                    'detail' => 'Unit precision "liter" already exists for this product.',
                    'source' => ['pointer' => '/data/relationships/unitPrecisions/data']
                ],
                [
                    'title' => 'unique entity constraint',
                    'detail' => 'This value is already used.',
                    'source' => ['pointer' => '/included/0/relationships/product/data']
                ]
            ],
            $response
        );
    }

    public function testUpdateWithSetUnitForExistingUnitPrecisionThatDuplicatesAnotherUnitPrecision()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        // guard
        self::assertNotEquals('liter', $product->getPrimaryUnitPrecision()->getUnit()->getCode());

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_duplicate_existing_unit_precision_update.yml'
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('included', $responseContent, '"included" section exists');
        self::assertCount(1, $responseContent['included'], '"included" section contains only one element');
        $updatedUnitPrecisionData = $responseContent['included'][0];
        self::assertEquals(
            'productunitprecisions',
            $updatedUnitPrecisionData['type'],
            'updated unit precision should be returned in "included" section'
        );
        self::assertEquals(
            'liter',
            $updatedUnitPrecisionData['relationships']['unit']['data']['id'],
            'update unit code for unit precision'
        );
    }

    public function testUpdateWithSetAttributeForExistingUnitPrecision()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        // guard
        self::assertNotEquals(5, $product->getPrimaryUnitPrecision()->getPrecision());

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            'update_product_update_existing_unit_precision.yml'
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('included', $responseContent, '"included" section exists');
        self::assertCount(1, $responseContent['included'], '"included" section contains only one element');
        $updatedUnitPrecisionData = $responseContent['included'][0];
        self::assertEquals(
            'productunitprecisions',
            $updatedUnitPrecisionData['type'],
            'updated unit precision should be returned in "included" section'
        );
        self::assertEquals(
            5,
            $updatedUnitPrecisionData['attributes']['precision'],
            'update attribute for unit precision'
        );
    }

    public function testDelete()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $id = $product->getId();
        $this->delete(
            [
                'entity' => 'products',
                'id' => (string)$id
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(Product::class, $id)
        );
    }

    public function testCreateForConfigurableProduct()
    {
        $this->post(
            ['entity' => 'products'],
            'create_configurable_product.yml'
        );
    }

    public function testCreateWithImage()
    {
        $response = $this->post(['entity' => 'products'], 'create_product_with_image.yml');
        $productId = $this->getResourceId($response);

        $product = $this->getEntityManager()->find(Product::class, $productId);

        $this->assertTrue($product !== null);
        $this->assertCount(1, $product->getImages());

        $items = $this->getContainer()
            ->get('oro_workflow.manager.system')
            ->getWorkflowItemsByEntity($product);

        $this->assertCount(1, $items);
    }
}
