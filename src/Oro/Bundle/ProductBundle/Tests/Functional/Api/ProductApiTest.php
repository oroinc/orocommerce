<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
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
            LoadCategoryData::class,
            LoadOrganizations::class,
            LoadBusinessUnitData::class,
            LoadProductUnitPrecisions::class,
            LoadProductTaxCodes::class,
            LoadAttributeFamilyData::class
        ]);
    }

    public function testCreateProduct()
    {
        $data = $this->getCreateProductRequest();
        $response = $this->post(
            ['entity' => 'products'],
            $data
        );

        $responseContent = json_decode($response->getContent());
        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $responseContent->data->id);

        $this->assertProductFields($product);
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
            $this->createUpdateRequest($product)
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());
        $referenceRepository = $this->getReferenceRepository();

        foreach ($product->getNames() as $name) {
            $localization = $name->getLocalization() === null ?
                'default'
                : $name->getLocalization()->getFormattingCode();
            $reference = LoadProductData::PRODUCT_1.'.names.'.$localization;
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
            'product_unit_precision.'.LoadProductData::PRODUCT_1.'.box',
            $newUnitPrecision
        );
        $referenceRepository->setReference(LoadProductData::PRODUCT_1, $product);

        $this->assertResponseContains('patch_update_entity.yml', $response);
    }

    /**
     * @param Product $product
     */
    protected function assertProductFields(Product $product)
    {
        $this->assertProductLocalizedValues($product);
        $this->assertProductAttributes($product);
    }

    /**
     * @param Product $product
     */
    private function assertProductLocalizedValues(Product $product)
    {
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
    }

    /**
     * @param Product $product
     */
    private function assertProductAttributes(Product $product)
    {
        $this->assertEquals('test-api-2', $product->getSku());
        $this->assertEquals('enabled', $product->getStatus());
        $this->assertEquals('simple', $product->getType());
        $this->assertEquals(true, $product->getFeatured());
        $this->assertEquals(false, $product->isNewArrival());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getCreateProductRequest()
    {
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL2);
        $businessUnit = $this->getReference('TestBusinessUnit');
        $organization = $businessUnit->getOrganization();
        $localization = $this->getReference('es');
        $bottleUnit = $this->getReference(LoadProductUnits::BOTTLE);
        $literUnit = $this->getReference(LoadProductUnits::LITER);
        $milliliterUnit = $this->getReference(LoadProductUnits::MILLILITER);
        $taxCodes = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX.'.'.LoadProductTaxCodes::TAX_3);
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "sku" => "test-api-2",
                    "status" => "enabled",
                    "variantFields" => [],
                    "productType" => "simple",
                    "featured" => true,
                    "newArrival" => false
                ],
                "relationships" => [
                    "owner" => [
                        "data" => [
                            "type" => "businessunits",
                            "id" => (string)$businessUnit->getId()
                        ]
                    ],
                    "organization" => [
                        "data" => [
                            "type" => "organizations",
                            "id" => (string)$organization->getId()
                        ]
                    ],
                    "names" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => "names-1"
                            ],
                            1 => [
                                "type" => "localizedfallbackvalues",
                                "id" => "names-2"
                            ]
                        ]
                    ],
                    "slugPrototypes" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => "slug-id-1"
                            ]
                        ]
                    ],
                    "taxCode" => [
                        "data" => [
                            "type" => "producttaxcodes",
                            "id" => (string)$taxCodes->getId()
                        ]
                    ],
                    "attributeFamily" => [
                        "data" => [
                            "type" => "attributefamilies",
                            "id" => (string)$attributeFamily->getId()
                        ]
                    ],
                    "primaryUnitPrecision" => [
                        "data" => [
                            "type" => "productunitprecisions",
                            "id" => "product-unit-precision-id-3"
                        ]
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "id" => "product-unit-precision-id-1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "id" => "product-unit-precision-id-2"
                            ],
                        ]
                    ],
                    "inventory_status" => [
                        "data" => [
                            "type" => "prodinventorystatuses",
                            "id" => "out_of_stock"
                        ]
                    ],
                    "manageInventory" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "1abcd"
                        ]
                    ],
                    "pageTemplate" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "page-template"
                        ]
                    ],
                    "inventoryThreshold" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "2abcd"
                        ]
                    ],
                    "minimumQuantityToOrder" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "3abcd"
                        ]
                    ],
                    "maximumQuantityToOrder" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "4abcd"
                        ]
                    ],
                    "decrementQuantity" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "5abcd"
                        ]
                    ],
                    "backOrder" => [
                        "data" => [
                            "type" => "entityfieldfallbackvalues",
                            "id" => "6abcd"
                        ]
                    ],
                    "category" => [
                        "data" => [
                            "type" => "categories",
                            "id" => (string) $category->getId()
                        ]
                    ]
                ]
            ],
            "included" => [
                0 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "1abcd",
                    "attributes" => [
                        "fallback" => "systemConfig",
                        "scalarValue" => null,
                        "arrayValue" => null
                    ]
                ],
                1 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "page-template",
                    "attributes" => [
                        "fallback" => null,
                        "scalarValue" => null,
                        "arrayValue" => "short"
                    ]
                ],
                2 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "2abcd",
                    "attributes" => [
                        "fallback" => null,
                        "scalarValue" => "31",
                        "arrayValue" => null
                    ]
                ],
                3 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "3abcd",
                    "attributes" => [
                        "fallback" => "systemConfig",
                        "scalarValue" => null,
                        "arrayValue" => null
                    ]
                ],
                4 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "4abcd",
                    "attributes" => [
                        "fallback" => null,
                        "scalarValue" => "12",
                        "arrayValue" => null
                    ]
                ],
                5 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "5abcd",
                    "attributes" => [
                        "fallback" => null,
                        "scalarValue" => "1",
                        "arrayValue" => null
                    ]
                ],
                6 => [
                    "type" => "entityfieldfallbackvalues",
                    "id" => "6abcd",
                    "attributes" => [
                        "fallback" => null,
                        "scalarValue" => "0",
                        "arrayValue" => null
                    ]
                ],
                7 => [
                    "type" => "localizedfallbackvalues",
                    "id" => "names-1",
                    "attributes" => [
                        "fallback" => null,
                        "string" => "Test product",
                        "text" => null
                    ],
                    "relationships" => ["localization" => ["data" => null]]
                ],
                8 => [
                    "type" => "localizedfallbackvalues",
                    "id" => "names-2",
                    "attributes" => [
                        "fallback" => null,
                        "string" => "Product in spanish",
                        "text" => null
                    ],
                    "relationships" => [
                        "localization" => [
                            "data" => [
                                "type" => "localizations",
                                "id" => (string)$localization->getId()
                            ]
                        ]
                    ]
                ],
                9 => [
                    "type" => "localizedfallbackvalues",
                    "id" => "slug-id-1",
                    "attributes" => [
                        "fallback" => null,
                        "string" => "test-prod-slug",
                        "text" => null
                    ],
                    "relationships" => ["localization" => ["data" => null]]
                ],
                10 => [
                    "type" => "productunitprecisions",
                    "id" => "product-unit-precision-id-1",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "5",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => $bottleUnit->getCode()
                            ]
                        ]
                    ]
                ],
                11 => [
                    "type" => "productunitprecisions",
                    "id" => "product-unit-precision-id-2",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "10",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => $literUnit->getCode()
                            ]
                        ]
                    ]
                ],
                12 => [
                    "type" => "productunitprecisions",
                    "id" => "product-unit-precision-id-3",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "2",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => $milliliterUnit->getCode()
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param Product $product
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createUpdateRequest(Product $product)
    {
        $productNameDefault = $this->getReference('product-1.names.default');
        $productNameEn = $this->getReference('product-1.names.en_US');
        $productSlugPrototypeDefault = $this->getReference('product-1.slugPrototypes.default');
        $productSlugPrototypeEn = $this->getReference('product-1.slugPrototypes.en_US');
        $productDescDefault = $this->getReference('product-1.descriptions.default');
        $productDescEn = $this->getReference('product-1.descriptions.en_US');
        $productShortDescDefault = $this->getReference('product-1.shortDescriptions.default');
        $productShortDescEn = $this->getReference('product-1.shortDescriptions.en_US');
        $productUnitPrecision1 = $this->getReference('product_unit_precision.product-1.bottle');
        $productUnitPrecision2 = $this->getReference('product_unit_precision.product-1.liter');
        $productUnitPrecision3 = $this->getReference('product_unit_precision.product-1.milliliter');
        $newProductUnit = $this->getReference(LoadProductUnits::BOX);
        $localization = $this->getReference('es');

        return [
            'data' => [
                'type' => 'products',
                'id' => (string)$product->getId(),
                "attributes" => [
                    "sku" => "new-sku",
                    "status" => "disabled",
                    "variantFields" => [],
                    "productType" => "simple",
                    "featured" => true,
                    "newArrival" => true
                ],
                'relationships' => [
                    "names" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productNameDefault->getId()
                            ],
                            1 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productNameEn->getId()
                            ],
                            2 => [
                                "type" => "localizedfallbackvalues",
                                "id" => "names-new"
                            ]
                        ]
                    ],
                    "slugPrototypes" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productSlugPrototypeDefault->getId(),
                            ],
                            1 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productSlugPrototypeEn->getId(),
                            ]
                        ]
                    ],
                    "descriptions" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productDescDefault->getId(),
                            ],
                            1 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productDescEn->getId()
                            ]
                        ]
                    ],
                    "shortDescriptions" => [
                        "data" => [
                            0 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productShortDescDefault->getId()
                            ],
                            1 => [
                                "type" => "localizedfallbackvalues",
                                "id" => (string)$productShortDescEn->getId()
                            ]
                        ]
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "id" => (string)$productUnitPrecision1->getId()
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "id" => (string)$productUnitPrecision2->getId()
                            ],
                            2 => [
                                "type" => "productunitprecisions",
                                "id" => (string)$productUnitPrecision3->getId()
                            ],
                            3 => [
                                "type" => "productunitprecisions",
                                "id" => "new-product-unit-precision"
                            ],
                        ]
                    ],
                    'inventory_status' => [
                        'data' => [
                            'type' => 'prodinventorystatuses',
                            'id' => 'out_of_stock',
                        ],
                    ],
                ],
            ],
            "included" => [
                0 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "localizedfallbackvalues",
                    "id" => (string)$productDescDefault->getId(),
                    "attributes" => [
                        "fallback" => null,
                        "string" => null,
                        "text" => "<b>Description Bold</b>"
                    ],
                    "relationships" => ["localization" => ["data" => null]]
                ],
                1 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "localizedfallbackvalues",
                    "id" => (string)$productNameDefault->getId(),
                    "attributes" => [
                        "fallback" => null,
                        "string" => "Test product changed",
                        "text" => null
                    ],
                    "relationships" => ["localization" => ["data" => null]]
                ],
                2 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "productunitprecisions",
                    "id" => (string)$productUnitPrecision1->getId(),
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "99",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => $productUnitPrecision1->getUnit()->getCode()
                            ]
                        ]
                    ]
                ],
                3 => [
                    "type" => "localizedfallbackvalues",
                    "id" => "names-new",
                    "attributes" => [
                        "fallback" => null,
                        "string" => "Product in spanish",
                        "text" => null
                    ],
                    "relationships" => [
                        "localization" => [
                            "data" => [
                                "type" => "localizations",
                                "id" => (string)$localization->getId()
                            ]
                        ]
                    ]
                ],
                4 => [
                    "type" => "productunitprecisions",
                    "id" => "new-product-unit-precision",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "15",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => $newProductUnit->getCode()
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }
}
