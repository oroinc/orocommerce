<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeGroupStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\AttributeFormViewListener;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AttributeFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var AttributeFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [
                    Product::class,
                    'wysiwyg',
                    new Config(
                        new FieldConfigId('attachment', Product::class, 'wysiwyg', WYSIWYGType::TYPE),
                        ['label' => 'wysiwyg field label']
                    )
                ],
                [
                    Product::class,
                    'multiFileField',
                    new Config(
                        new FieldConfigId('extend', Product::class, 'multiFileField', 'multiFile'),
                        ['label' => 'multiFile field label']
                    )
                ],
                [
                    Product::class,
                    'multiImageField',
                    new Config(
                        new FieldConfigId('extend', Product::class, 'multiImageField', 'multiImage'),
                        ['label' => 'multiImage field label']
                    )
                ],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
                ['wysiwyg field label', [], null, null, 'translated wysiwyg field label'],
                ['multiFile field label', [], null, null, 'translated multiFile field label'],
                ['multiImage field label', [], null, null, 'translated multiImage field label'],
            ]);

        $this->listener = new AttributeFormViewListener(
            $this->attributeManager,
            $entityConfigProvider,
            $translator
        );
    }

    /**
     * @dataProvider viewListDataProvider
     */
    public function testViewList(
        array $groupsData,
        array $scrollData,
        string $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class),
        ]);

        $this->environment->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $listEvent = new BeforeListRenderEvent($this->environment, new ScrollData($scrollData), $entity);
        $this->listener->onViewList($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function viewListDataProvider(): array
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);

        $attributeVisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => true],
                    'form' => ['is_enabled' => true]
                ]
            ]
        );

        $inventoryStatus = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'inventory_status']);
        $images = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'images']);
        $productPriceAttributesPrices =
            $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'productPriceAttributesPrices']);
        $shortDescription = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'shortDescriptions']);
        $descriptions = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'descriptions']);
        $wysiwyg = $this->getEntity(
            FieldConfigModel::class,
            ['id' => 1, 'fieldName' => 'wysiwyg', 'type' => WYSIWYGType::TYPE, 'data' => [
                'view' => ['is_displayable' => true],
                'form' => ['is_enabled' => true]
            ]]
        );

        return [
            'move attribute field to other group not allowed (inventory_status)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$inventoryStatus]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (images)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$images]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (productPriceAttributesPrices)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$productPriceAttributesPrices]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (shortDescriptions)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$shortDescription]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'shortDescriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'shortDescriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (descriptions)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$descriptions]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'descriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'descriptions' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => ['someField' => 'field template'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move wysiwyg attribute field to own group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$wysiwyg]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'wysiwyg' => [
                            'title' => 'translated wysiwyg field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'wysiwyg' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
            'move multiFile attribute field to own group' => [
                'groupsData' => [
                    [
                        'group' => $this->getEntity(
                            AttributeGroupStub::class,
                            [
                                'code' => 'group1',
                                'label' => $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title'])
                            ]
                        ),
                        'attributes' => [
                            $this->getEntity(
                                FieldConfigModel::class,
                                [
                                    'id' => 1,
                                    'fieldName' => 'multiFileField',
                                    'type' => 'multiFile',
                                    'data' => [
                                        'view' => ['is_displayable' => true],
                                        'form' => ['is_enabled' => true],
                                    ],
                                ]
                            ),
                        ]
                    ],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'multiFileField' => [
                            'title' => 'translated multiFile field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'multiFileField' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
            'move multiImage attribute field to own group' => [
                'groupsData' => [
                    [
                        'group' => $this->getEntity(
                            AttributeGroupStub::class,
                            [
                                'code' => 'group1',
                                'label' => $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title'])
                            ]
                        ),
                        'attributes' => [
                            $this->getEntity(
                                FieldConfigModel::class,
                                [
                                    'id' => 1,
                                    'fieldName' => 'multiImageField',
                                    'type' => 'multiImage',
                                    'data' => [
                                        'view' => ['is_displayable' => true],
                                        'form' => ['is_enabled' => true],
                                    ],
                                ]
                            ),
                        ]
                    ],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'multiImageField' => [
                            'title' => 'translated multiImage field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'multiImageField' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider editDataProvider
     */
    public function testOnEdit(
        array $groupsData,
        array $scrollData,
        string $fieldName,
        string $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class),
        ]);

        $this->environment->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);

        $attributeView = new FormView();
        if (!$templateHtml) {
            $attributeView->setRendered();
        }

        $formView = new FormView();
        $formView->children[$fieldName] = $attributeView;

        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity, $formView);
        $this->listener->onEdit($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function editDataProvider(): array
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);

        $attributeVisible = $this->getEntity(
            FieldConfigModel::class,
            [
                'id' => 1,
                'fieldName' => 'someField',
                'data' => [
                    'view' => ['is_displayable' => true],
                    'form' => ['is_enabled' => true]
                ]
            ]
        );

        $inventoryStatus = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'inventory_status']);
        $images = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'images']);
        $productPriceAttributesPrices =
            $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'productPriceAttributesPrices']);
        $wysiwyg = $this->getEntity(
            FieldConfigModel::class,
            ['id' => 1, 'fieldName' => 'wysiwyg', 'type' => WYSIWYGType::TYPE, 'data' => [
                'view' => ['is_displayable' => true],
                'form' => ['is_enabled' => true]
            ]]
        );

        return [
            'move attribute field to other group not allowed (inventory_status)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$inventoryStatus]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'inventory_status',
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'inventory_status' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (images)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$images]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'images',
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'images' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group not allowed (productPriceAttributesPrices)' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$productPriceAttributesPrices]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'productPriceAttributesPrices',
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'productPriceAttributesPrices' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move attribute field to other group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$attributeVisible]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'someField',
                'templateHtml' => '',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'otherField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                        'group1' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'move wysiwyg attribute field to own group' => [
                'groupsData' => [
                    ['group' => $group1, 'attributes' => [$wysiwyg]],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'wysiwyg',
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'wysiwyg' => [
                            'title' => 'translated wysiwyg field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'wysiwyg' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
            'move multiFile attribute field to own group' => [
                'groupsData' => [
                    [
                        'group' => $this->getEntity(
                            AttributeGroupStub::class,
                            [
                                'code' => 'group1',
                                'label' => $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title'])
                            ]
                        ),
                        'attributes' => [
                            $this->getEntity(
                                FieldConfigModel::class,
                                [
                                    'id' => 1,
                                    'fieldName' => 'multiFileField',
                                    'type' => 'multiFile',
                                    'data' => [
                                        'view' => ['is_displayable' => true],
                                        'form' => ['is_enabled' => true],
                                    ],
                                ]
                            ),
                        ]
                    ],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'multiFileField',
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'multiFileField' => [
                            'title' => 'translated multiFile field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'multiFileField' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
            'move multiImage attribute field to own group' => [
                'groupsData' => [
                    [
                        'group' => $this->getEntity(
                            AttributeGroupStub::class,
                            [
                                'code' => 'group1',
                                'label' => $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title'])
                            ]
                        ),
                        'attributes' => [
                            $this->getEntity(
                                FieldConfigModel::class,
                                [
                                    'id' => 1,
                                    'fieldName' => 'multiImageField',
                                    'type' => 'multiImage',
                                    'data' => [
                                        'view' => ['is_displayable' => true],
                                        'form' => ['is_enabled' => true],
                                    ],
                                ]
                            ),
                        ]
                    ],
                ],
                'scrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fieldName' => 'multiImageField',
                'templateHtml' => 'expected template data',
                'expectedData' => [
                    ScrollData::DATA_BLOCKS => [
                        'existingGroup' => [
                            'title' => 'Group1Title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'someField' => 'field template'
                                    ],
                                ],
                            ],
                        ],
                        'multiImageField' => [
                            'title' => 'translated multiImage field label',
                            'useSubBlockDivider' => true,
                            'subblocks' => [
                                [
                                    'data' => [
                                        'multiImageField' => 'expected template data'
                                    ],
                                ],
                            ],
                            'priority' => 501,
                        ],
                    ],
                ],
            ],
        ];
    }
}
