<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeGroupStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\EventListener\AttributeFormViewListener;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environment;

    /**
     * @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeManager;

    /**
     * @var AttributeFormViewListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->environment = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AttributeFormViewListener($this->attributeManager);
    }

    /**
     * @dataProvider viewListDataProvider
     * @param array $groupsData
     * @param array $scrollData
     * @param string $templateHtml
     * @param array $expectedData
     */
    public function testViewList(
        array $groupsData,
        array $scrollData,
        $templateHtml,
        array $expectedData
    ) {
        $entity = $this->getEntity(TestActivityTarget::class, [
            'attributeFamily' => $this->getEntity(AttributeFamily::class),
        ]);

        $this->environment
            ->expects($templateHtml ? $this->once() : $this->never())
            ->method('render')
            ->willReturn($templateHtml);

        $this->attributeManager
            ->expects($this->once())
            ->method('getGroupsWithAttributes')
            ->willReturn($groupsData);

        $scrollData = new ScrollData($scrollData);
        $listEvent = new BeforeListRenderEvent($this->environment, $scrollData, $entity);
        $this->listener->onViewList($listEvent);

        $this->assertEquals($expectedData, $listEvent->getScrollData()->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function viewListDataProvider()
    {
        $label = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'Group1Title']);
        $group1 = $this->getEntity(AttributeGroupStub::class, ['code' => 'group1', 'label' => $label]);

        $attribute1 = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'someField']);

        $inventoryStatus = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'inventory_status']);
        $images = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'images']);
        $productPriceAttributesPrices =
            $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'productPriceAttributesPrices']);

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
                'templateHtml' => false,
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
                'templateHtml' => false,
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
                'templateHtml' => false,
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
                    ['group' => $group1, 'attributes' => [$attribute1]],
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
                'templateHtml' => false,
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
        ];
    }
}
