<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchMappingListener;
use Oro\Bundle\ProductBundle\Search\ProductIndexFieldsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\EnumSearchableAttributeTypeStub;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class WebsiteSearchMappingListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeManager;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configurationProvider;

    /** @var ProductIndexFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldsProvider;

    /** @var SearchableInformationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchableProvider;

    /** @var WebsiteSearchMappingListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);
        $this->configurationProvider = $this->createMock(AttributeConfigurationProvider::class);
        $this->fieldsProvider = $this->createMock(ProductIndexFieldsProvider::class);
        $this->searchableProvider = $this->createMock(SearchableInformationProvider::class);

        $this->listener = new WebsiteSearchMappingListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            $this->configurationProvider,
            $this->fieldsProvider,
            $this->searchableProvider
        );
    }

    public function testOnWebsiteSearchMappingWithoutAttributes()
    {
        $event = new SearchMappingCollectEvent([]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([]);

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals([], $event->getMappingConfig());
    }

    public function testOnWebsiteSearchMappingWithoutAttributeType()
    {
        $event = new SearchMappingCollectEvent([]);

        $attribute = new FieldConfigModel('attribute');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute]);

        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($attribute)
            ->willReturn(null);

        $this->configurationProvider->expects($this->never())->method($this->anything());
        $this->fieldsProvider->expects($this->never())->method($this->anything());

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals([], $event->getMappingConfig());
    }

    public function testOnWebsiteSearchMappingNotActiveAttribute()
    {
        $event = new SearchMappingCollectEvent([]);

        $attribute = new FieldConfigModel('attribute');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute]);

        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($attribute)
            ->willReturn(new EnumSearchableAttributeType(new EnumAttributeType()));

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeActive')
            ->with($attribute)
            ->willReturn(false);

        $this->configurationProvider->expects($this->never())->method('isAttributeFilterable');
        $this->configurationProvider->expects($this->never())->method('isAttributeSortable');
        $this->fieldsProvider->expects($this->never())->method($this->anything());

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals([], $event->getMappingConfig());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchMappingSortableAttribute()
    {
        $event = $this->createEventWithBasicConfiguration();

        $attribute1 = new FieldConfigModel('attribute1');
        $attribute2 = new FieldConfigModel('attribute2');
        $attribute3 = new FieldConfigModel('attribute3');
        $attribute4 = new FieldConfigModel('attribute4');
        $attribute5 = new FieldConfigModel('attribute5');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5]);

        $attributeType = new EnumSearchableAttributeTypeStub(new EnumAttributeType());

        $this->attributeTypeRegistry->expects($this->any())
            ->method('getAttributeType')
            ->willReturn($attributeType);

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeActive')
            ->willReturn(true);

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeFilterable')
            ->willReturnMap(
                [
                    [$attribute1, true],
                    [$attribute2, false],
                    [$attribute3, false],
                    [$attribute4, false],
                    [$attribute5, true]
                ]
            );

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeSearchable')
            ->willReturnMap(
                [
                    [$attribute1, false],
                    [$attribute2, false],
                    [$attribute3, true],
                    [$attribute4, true],
                    [$attribute5, true]
                ]
            );

        $this->searchableProvider->expects($this->any())
            ->method('getAttributeSearchBoost')
            ->willReturnMap(
                [
                    [$attribute1, null],
                    [$attribute2, 1.0],
                    [$attribute3, 0.0],
                    [$attribute4, null],
                    [$attribute5, 1.0]
                ]
            );

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeSortable')
            ->willReturnMap(
                [
                    [$attribute1, false],
                    [$attribute2, true],
                    [$attribute3, false],
                    [$attribute4, false],
                    [$attribute5, true]
                ]
            );

        $this->fieldsProvider->expects($this->any())
            ->method('isForceIndexed')
            ->willReturnMap(
                [
                    [$attribute1->getFieldName(), false],
                    [$attribute2->getFieldName(), false],
                    [$attribute3->getFieldName(), true],
                    [$attribute4->getFieldName(), false],
                    [$attribute5->getFieldName(), false]
                ]
            );

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals(
            [
                Product::class   => [
                    'alias'  => 'products',
                    'synonyms_enabled' => false,
                    'fields' => [
                        'firstname' => [
                            'name'            => 'firstname',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        'lasttname' => [
                            'name'            => 'lasttname',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        'attribute1' => [
                            'name'            => 'attribute1',
                            'type'            => 'integer',
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute1->getFieldName().'_enum.'.EnumIdPlaceholder::NAME => [
                            'name'            => $attribute1->getFieldName().'_enum.'.EnumIdPlaceholder::NAME,
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute2->getFieldName().'_priority'                 => [
                            'name'            => $attribute2->getFieldName().'_priority',
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute3->getFieldName().'_enum.'.EnumIdPlaceholder::NAME => [
                            'name'            => $attribute3->getFieldName().'_enum.'.EnumIdPlaceholder::NAME,
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute3->getFieldName().'_priority'                 => [
                            'name'            => $attribute3->getFieldName().'_priority',
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute5->getFieldName().'_enum.'.EnumIdPlaceholder::NAME                 => [
                            'name'            => $attribute5->getFieldName().'_enum.'.EnumIdPlaceholder::NAME,
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute5->getFieldName().'_priority'                 => [
                            'name'            => $attribute5->getFieldName().'_priority',
                            'type'            => Query::TYPE_INTEGER,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute5->getFieldName().'_searchable'                 => [
                            'name'            => $attribute5->getFieldName().'_searchable',
                            'type'            => Query::TYPE_TEXT,
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ]
                    ]
                ],
                \stdClass::class => [
                    'alias'  => 'std',
                    'synonyms_enabled' => false,
                    'fields' => [
                        'first' => [
                            'name'            => 'first',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ]
                    ]
                ]
            ],
            $event->getMappingConfig()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchMappingNoDuplicates()
    {
        $event = $this->createEventWithBasicConfiguration();

        $attribute1 = new FieldConfigModel('attribute1');
        $attribute2 = new FieldConfigModel('attribute2');
        $attribute3 = new FieldConfigModel('attribute3');
        $attribute4 = new FieldConfigModel('attribute4');
        $attribute5 = new FieldConfigModel('attribute5');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5]);

        $attributeType = new StringSearchableAttributeType(new StringAttributeType());

        $this->attributeTypeRegistry->expects($this->any())
            ->method('getAttributeType')
            ->willReturn($attributeType);

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeActive')
            ->willReturn(true);

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeFilterable')
            ->willReturnMap(
                [
                    [$attribute1, true],
                    [$attribute2, false],
                    [$attribute3, false],
                    [$attribute4, false],
                    [$attribute5, false]
                ]
            );

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeSearchable')
            ->willReturnMap(
                [
                    [$attribute1, false],
                    [$attribute2, false],
                    [$attribute3, true],
                    [$attribute4, true],
                    [$attribute5, true]
                ]
            );

        $this->searchableProvider->expects($this->any())
            ->method('getAttributeSearchBoost')
            ->willReturnMap(
                [
                    [$attribute1, null],
                    [$attribute2, 1.0],
                    [$attribute3, 0.0],
                    [$attribute4, null],
                    [$attribute5, 1.0]
                ]
            );

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeSortable')
            ->willReturnMap(
                [
                    [$attribute1, false],
                    [$attribute2, true],
                    [$attribute3, false],
                    [$attribute4, false],
                    [$attribute5, true]
                ]
            );

        $this->fieldsProvider->expects($this->any())
            ->method('isForceIndexed')
            ->willReturnMap(
                [
                    [$attribute1->getFieldName(), false],
                    [$attribute2->getFieldName(), false],
                    [$attribute3->getFieldName(), true],
                    [$attribute4->getFieldName(), false],
                    [$attribute5->getFieldName(), false]
                ]
            );

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals(
            [
                Product::class   => [
                    'alias'  => 'products',
                    'synonyms_enabled' => false,
                    'fields' => [
                        'firstname' => [
                            'name'            => 'firstname',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        'lasttname' => [
                            'name'            => 'lasttname',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        'attribute1' => [
                            'name'            => 'attribute1',
                            'type'            => 'integer',
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute1->getFieldName() => [
                            'name'            => $attribute1->getFieldName(),
                            'type'            => Query::TYPE_TEXT,
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute2->getFieldName() => [
                            'name'            => $attribute2->getFieldName(),
                            'type'            => Query::TYPE_TEXT,
                            'store'           => true,
                            'fulltext'        => false,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute3->getFieldName() => [
                            'name'            => $attribute3->getFieldName(),
                            'type'            => Query::TYPE_TEXT,
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                        $attribute5->getFieldName() => [
                            'name'            => $attribute5->getFieldName(),
                            'type'            => Query::TYPE_TEXT,
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ],
                    ]
                ],
                \stdClass::class => [
                    'alias'  => 'std',
                    'synonyms_enabled' => false,
                    'fields' => [
                        'first' => [
                            'name'            => 'first',
                            'type'            => 'text',
                            'store'           => true,
                            'fulltext'        => true,
                            'organization_id' => null,
                            'group' => 'main'
                        ]
                    ]
                ]
            ],
            $event->getMappingConfig()
        );
    }

    protected function createEventWithBasicConfiguration(): SearchMappingCollectEvent
    {
        return new SearchMappingCollectEvent(
            [
                Product::class   => [
                    'alias'  => 'products',
                    'fields' => [
                        'firstname'  => [
                            'name'  => 'firstname',
                            'type'  => 'text',
                            'store' => true
                        ],
                        'lasttname'  => [
                            'name'  => 'lasttname',
                            'type'  => 'text',
                            'store' => true
                        ],
                        'attribute1' => [
                            'name' => 'attribute1',
                            'type' => 'integer'
                        ]
                    ]
                ],
                \stdClass::class => [
                    'alias'  => 'std',
                    'fields' => [
                        'first' => [
                            'name' => 'first',
                            'type' => 'text'
                        ]
                    ]
                ]
            ]
        );
    }
}
