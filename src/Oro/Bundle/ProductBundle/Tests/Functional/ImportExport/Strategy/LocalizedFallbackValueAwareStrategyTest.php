<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizedFallbackValueAwareStrategyTest extends WebTestCase
{
    use EntityTrait;

    /** @var LocalizedFallbackValueAwareStrategy */
    protected $strategy;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $container = $this->getContainer();

        $this->loadFixtures([LoadProductData::class]);

        $container->get('oro_importexport.field.database_helper')->onClear();

        $this->strategy = new LocalizedFallbackValueAwareStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.import.helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setLocalizedFallbackValueClass(
            $container->getParameter('oro_locale.entity.localized_fallback_value.class')
        );
    }

    /**
     * @param array $entityData
     * @param array $expectedNames
     * @param array $itemData
     * @param array $expectedSlugPrototypes
     *
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $entityData = [],
        array $expectedNames = [],
        array $itemData = [],
        array $expectedSlugPrototypes = []
    ) {
        $entityData = $this->convertArrayToEntities($entityData);

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

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

        /** @var \Oro\Bundle\ProductBundle\Entity\Product $entity */
        $entity = $this->getEntity($productClass, $entityData);
        $entity->setInventoryStatus($inventoryStatus);

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['code' => 'default_family']);
        $entity->setAttributeFamily($attributeFamily);
        /** @var \Oro\Bundle\ProductBundle\Entity\Product $result */
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
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            'testProperties' => [
                                'string' => 'product-1 Default Name'
                            ],
                        ],
                        [
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
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
                        'reference' => 'product-1.names.en_US',
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
     * @param array $entityData
     * @param callable $resultCallback
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

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName($productClass);

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue $inventoryStatus */
        $inventoryStatus = $this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find('in_stock');

        /** @var \Oro\Bundle\ProductBundle\Entity\Product $entity */
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
            'new product, no fallback from another entity' => [
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
                    $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $product);

                    /** @var \Oro\Bundle\ProductBundle\Entity\Product $product */
                    $this->assertNull($product->getId());
                    $this->assertEmpty($product->getNames()->toArray());
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
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            'testProperties' => ['string' => 'product-4 Default Name']
                        ],
                        [
                            'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                            'testProperties' => [
                                'string' => 'product-4 en_US Name',
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

                    /** @var \Oro\Bundle\ProductBundle\Entity\Product $product */
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
}
