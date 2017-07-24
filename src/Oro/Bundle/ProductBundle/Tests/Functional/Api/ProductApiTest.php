<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;


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
            LoadAttributeFamilyData::class,
            LoadCategoryData::class,
        ]);
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
            ['entity' => 'products', 'id' => (string) $product->getId()],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string) $product->getId(),
                    'relationships' => [
                        'inventory_status' => [
                            'data' => [
                                'type' => 'prodinventorystatuses',
                                'id' => 'out_of_stock',
                            ],
                        ],
                    ],
                ]
            ]
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());
        $this->getReferenceRepository()->setReference(LoadProductData::PRODUCT_1, $product);

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
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $this->getEntityType(Product::class)]),
            $this->getRequestData(__DIR__ . '/requests/create_product_3.yml')
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);

        $containsErrorMessage = false;

        foreach ($content['errors'] as $error) {
            if ($error['source']['pointer'] == "/data/relationships/pageTemplate/data"
                && $error['detail'] == "The selected value is not valid."
            ) {
                $containsErrorMessage = true;
                break;
            }
        }

        $this->assertTrue($containsErrorMessage);
        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBy(['sku' => 'sku-test-api-3']);
        $this->assertNull($product);
    }
}
