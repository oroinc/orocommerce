<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

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
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductApiTest extends RestJsonApiTestCase
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

    public function testCreateProduct()
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

    /**
     * @param array $parameters
     * @param string $expectedDataFileName
     *
     * @dataProvider getListDataProvider
     */
    public function testGetList(array $parameters, $expectedDataFileName)
    {
        $response = $this->cget(['entity' => 'products'], $parameters);

        $this->assertResponseContains($expectedDataFileName, $response);
    }

    public function testGet()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $response = $this->get(
            ['entity' => 'products', 'id' => $product->getId()],
            []
        );

        $this->assertResponseContains('get_product_by_id.yml', $response);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'filter' => [
                        'sku' => '@product-1->sku',
                    ],
                ],
                'expectedDataFileName' => 'cget_filter_by_product.yml',
            ],
            'filter by Products with different inventory status' => [
                'parameters' => [
                    'filter' => [
                        'sku' => ['@product-2->sku', '@product-3->sku'],
                    ],
                ],
                'expectedDataFileName' => 'cget_filter_by_products_by_inventory_status.yml',
            ],
        ];
    }

    public function testUpdateEntity()
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

    public function testProductPageTemplateScalarValue()
    {
        // pageTemplate = 'short'
        $response = $this->post(
            ['entity' => $this->getEntityType(Product::class)],
            __DIR__ . '/requests/create_product_1.yml'
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBy(['sku' => 'sku-test-api-1']);

        $pageTemplate = $product->getPageTemplate();
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $pageTemplate);
        $this->assertArrayHasKey(ProductType::PAGE_TEMPLATE_ROUTE_NAME, $pageTemplate->getOwnValue());
        $this->assertEquals('short', $pageTemplate->getArrayValue()[ProductType::PAGE_TEMPLATE_ROUTE_NAME]);
        $this->assertNull($pageTemplate->getScalarValue());
        $this->assertNull($pageTemplate->getFallback());
    }

    public function testProductPageTemplateFallbackValue()
    {
        // pageTemplate = 'systemConfig'
        $response = $this->post(
            ['entity' => $this->getEntityType(Product::class)],
            __DIR__ . '/requests/create_product_2.yml'
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBy(['sku' => 'sku-test-api-2']);

        $pageTemplate = $product->getPageTemplate();
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $pageTemplate);
        $this->assertEquals('systemConfig', $pageTemplate->getFallback());
        $this->assertNull($pageTemplate->getScalarValue());
    }

    public function testProductPageTemplateInvalidValue()
    {
        // pageTemplate = 'invalid-value'
        $response = $this->post(
            ['entity' => $this->getEntityType(Product::class)],
            $this->getRequestData(__DIR__ . '/requests/create_product_3.yml'),
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product page template constraint',
                'detail' => 'The selected value is not valid.',
                'source' => ['pointer' => '/data/relationships/pageTemplate/data']
            ],
            $response
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBy(['sku' => 'sku-test-api-3']);
        $this->assertNull($product);
    }

    public function testCreateProductWithEmptyNames()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'create_product_empty_names.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/relationships/names/data']
            ],
            $response
        );
    }

    public function testCreateProductWithInvalidProductUnit()
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

    public function testCreateProductWithDuplicateUnitPrecision()
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

    public function testUpdateProductWithInvalidProductUnit()
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

    public function testUpdateProductWhenUnitForNewunitPrecisionIsNotProvided()
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

    public function testUpdateProductWithDuplicateUnitPrecision()
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

    public function testUpdateProductWithDuplicateExistingUnitPrecision()
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

    public function testUpdateProductWithSetUnitForExistingUnitPrecisionThatDuplicatesAnotherUnitPrecision()
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

    public function testUpdateProductWithSetAttributeForExistingUnitPrecision()
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

    public function testDeleteAction()
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

    public function testCreateConfigurableProduct()
    {
        $response = $this->post(
            [
                'entity' => $this->getEntityType(Product::class)
            ],
            __DIR__ . '/requests/create_configurable_product.yml'
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
    }

    public function testCreateProductWithImage()
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
