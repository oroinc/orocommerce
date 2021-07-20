<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedFallbackValueAwareStrategyTest extends WebTestCase
{
    use EntityTrait;

    /** @var LocalizedFallbackValueAwareStrategy */
    protected $strategy;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $container = $this->getContainer();

        $this->loadFixtures([LoadProductData::class]);

        $container->get('oro_importexport.field.database_helper')->onClear();

        $this->strategy = new LocalizedFallbackValueAwareStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.configurable_import_strategy_helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setLocalizedFallbackValueClass(AbstractLocalizedFallbackValue::class);
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $entityData = [],
        array $expectedNames = [],
        array $itemData = [],
        array $expectedSlugPrototypes = []
    ) {
        $entityData = $this->convertArrayToEntities($entityData);

        $productClass = Product::class;

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName($productClass);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find('in_stock');

        /** @var Product $existingEntity */
        $existingEntity = $this->getReference($entityData['sku']);
        $this->assertNotEmpty($existingEntity->getNames());
        $this->assertNotEmpty($existingEntity->getSlugPrototypes());

        /** @var Product $entity */
        $entity = $this->getEntity($productClass, $entityData);
        $entity->setInventoryStatus($inventoryStatus);

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['code' => 'default_family']);
        $entity->setAttributeFamily($attributeFamily);
        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertLocalizedFallbackValues($expectedNames, $result->getNames());
        $this->assertLocalizedFallbackValues($expectedSlugPrototypes, $result->getSlugPrototypes());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processDataProvider()
    {
        return [
            [
                'entityData' => [
                    'sku' => 'product-1',
                    'primaryUnitPrecision' => [
                        'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        'testProperties' => [
                            'unit' => $this->getEntity(
                                'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'kg']
                            ),
                            'precision' => 3,
                        ]
                    ],
                    'names' => [
                        [
                            'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductName',
                            'testProperties' => [
                                'string' => 'product-1 Default Name'
                            ],
                        ],
                        [
                            'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductName',
                            'testProperties' => [
                                'string' => 'product-1 en_CA Name',
                                'localization' => [
                                    'testEntity' => Localization::class,
                                    'testProperties' => [
                                        'name' => 'English (Canada)',
                                    ],
                                ],
                            ]
                        ],
                    ],
                    'slugPrototypes' => [
                        [
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            'testProperties' => [
                                'string' => 'product-1-default-slug-prototype-updated'
                            ]
                        ],
                        [
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            'testProperties' => [
                                'string' => 'product-1-en-ca-slug-prototype-added',
                                'localization' => [
                                    'testEntity' => Localization::class,
                                    'testProperties' => [
                                        'name' => 'English (Canada)',
                                    ],
                                ],
                            ]
                        ],
                    ]
                ],
                'expectedNames' => [
                    'default' => [
                        'reference' => 'product-1.names.default',
                        'string' => 'product-1 Default Name',
                        'text' => null,
                        'fallback' => null,
                    ],
                    'English (Canada)' => [
                        'reference' => 'product-1.names.en_CA',
                        'string' => 'product-1 en_CA Name',
                        'text' => null,
                        'fallback' => null,
                    ],
                ],
                'itemData' => [
                    'sku' => 'product-1',
                    'names' => [
                        null => [
                            'string' => 'product-1 Default Name'
                        ],
                        'English (Canada)' => [
                            'string' => 'product-1 en_CA Name',
                        ],
                    ],
                    'slugPrototypes' => [
                        null => [
                            'string' => 'product-1-default-slug-prototype-updated'
                        ],
                        'English (Canada)' => [
                            'string' => 'product-1-en-ca-slug-prototype-added',
                        ],
                    ],
                ],
                'expectedSlugPrototypes' => [
                    'default' => [
                        'reference' => 'product-1.slugPrototypes.default',
                        'string' => 'product-1-default-slug-prototype-updated',
                        'text' => null,
                        'fallback' => null,
                    ],
                    'English (Canada)' => [
                        'reference' => 'product-1.slugPrototypes.en_CA',
                        'string' => 'product-1-en-ca-slug-prototype-added',
                        'text' => null,
                        'fallback' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider skippedDataProvider
     */
    public function testProcessSkipped(array $entityData, callable $resultCallback)
    {
        $entityData = $this->convertArrayToEntities($entityData);

        $entityData['attributeFamily'] = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => $entityData['attributeFamily']]);

        $productClass = Product::class;

        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName($productClass);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find('in_stock');

        /** @var Product $entity */
        $entity = $this->getEntity($productClass, $entityData);
        $entity->setInventoryStatus($inventoryStatus);
        $entity->setOwner(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:BusinessUnit')->findOneBy([])
        );

        $resultCallback($this->strategy->process($entity));
    }

    /**
     * @return array
     */
    public function skippedDataProvider()
    {
        return [
            'New product will not be imported if names is empty' => [
                [
                    'sku' => 'new_sku',
                    'attributeFamily' => 'default_family',
                    'primaryUnitPrecision' => [
                        'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        'testProperties' => [
                            'unit' => $this->getEntity(
                                'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'kg']
                            ),
                            'precision' => 3,
                        ]
                    ],
                ],
                function ($product) {
                    $this->assertNull($product);
                },
            ],
            'existing product with, id not mapped for new fallback' => [
                [
                    'sku' => 'product-4',
                    'attributeFamily' => 'default_family',
                    'primaryUnitPrecision' => [
                        'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
                        'testProperties' => [
                            'unit' => $this->getEntity(
                                'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                                ['code' => 'each']
                            ),
                            'precision' => 0,
                        ]
                    ],
                    'names' => [
                        [
                            'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductName',
                            'testProperties' => ['string' => 'product-4 Default Name']
                        ],
                        [
                            'testEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductName',
                            'testProperties' => [
                                'string' => 'product-4 en_CA Name',
                                'localization' => [
                                    'testEntity' => Localization::class,
                                    'testProperties' => [
                                        'name' => 'English (United States)',
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                function ($product) {
                    $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $product);

                    /** @var Product $product */
                    $this->assertNotNull($product->getId());
                    $this->assertNotEmpty($product->getNames()->toArray());
                    $this->assertNull($product->getNames()->last()->getId());
                },
            ],
        ];
    }

    /**
     * @param array $expectedValues
     * @param array|Collection $actualValues
     */
    protected function assertLocalizedFallbackValues(array $expectedValues, $actualValues)
    {
        $this->assertCount(count($expectedValues), $actualValues);
        foreach ($actualValues as $localizedFallbackValue) {
            $localizationCode = LocalizationCodeFormatter::formatName($localizedFallbackValue->getLocalization());
            $this->assertArrayHasKey($localizationCode, $expectedValues);

            $expectedValue = $expectedValues[$localizationCode];
            if (!empty($expectedValue['reference'])) {
                /**
                 * Validate that id matched from existing collection and does not affect other entities
                 * @var LocalizedFallbackValue $reference
                 */
                $reference = $this->getReference($expectedValue['reference']);
                $this->assertEquals($reference->getId(), $localizedFallbackValue->getId());
            } else {
                $this->assertNull($localizedFallbackValue->getId());
            }

            $this->assertEquals($expectedValue['text'], $localizedFallbackValue->getText());
            $this->assertEquals($expectedValue['string'], $localizedFallbackValue->getString());
            $this->assertEquals($expectedValue['fallback'], $localizedFallbackValue->getFallback());
        }
    }

    public function testNewText(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => 'new value', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setText('new value');
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals('new value', $result->getShortDescriptions()->first()->getText());
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testNewFallback(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::PARENT_LOCALIZATION]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setFallback(FallbackType::PARENT_LOCALIZATION);
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(FallbackType::PARENT_LOCALIZATION, $result->getShortDescriptions()->first()->getFallback());
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testUpdateText(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => 'product-1.shortDescriptions.en_CA_new', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('en_CA'));
        $description->setText('product-1.shortDescriptions.en_CA_new');
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            'product-1.shortDescriptions.en_CA_new',
            $result->getShortDescriptions()->first()->getText()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testUpdateFallback(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setFallback(FallbackType::SYSTEM);
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            FallbackType::SYSTEM,
            $result->getShortDescriptions()->first()->getFallback()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testSwitchTextToFallback(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setFallback(FallbackType::SYSTEM);
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            FallbackType::SYSTEM,
            $result->getShortDescriptions()->first()->getFallback()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testSwitchFallbackToText(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => 'text', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setText('text');
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            'text',
            $result->getShortDescriptions()->first()->getText()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testBothFallbackAndValueWithValuesAreValid(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => 'text', 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $description->setText('text');
        $description->setFallback(FallbackType::SYSTEM);
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertNotNull($result->getShortDescriptions()->first()->getFallback());
        $this->assertNotNull($result->getShortDescriptions()->first()->getText());
    }

    public function testBothFallbackAndValueWithoutValuesAreValid(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => null, 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $description = new ProductShortDescription();
        $description->setLocalization($this->getReference('es'));
        $entity->addShortDescription($description);

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }
}
