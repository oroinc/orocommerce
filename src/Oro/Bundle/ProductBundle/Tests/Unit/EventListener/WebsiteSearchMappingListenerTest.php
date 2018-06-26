<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchMappingListener;
use Oro\Bundle\ProductBundle\Search\ProductIndexFieldsProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;

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

    /** @var WebsiteSearchMappingListener */
    protected $listener;

    protected function setUp()
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);
        $this->configurationProvider = $this->createMock(AttributeConfigurationProvider::class);
        $this->fieldsProvider = $this->createMock(ProductIndexFieldsProvider::class);

        $this->listener = new WebsiteSearchMappingListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            $this->configurationProvider,
            $this->fieldsProvider
        );
    }

    public function testOnWebsiteSearchMappingWithoutAttributes()
    {
        $event = new WebsiteSearchMappingEvent();

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([]);

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals([], $event->getConfiguration());
    }

    public function testOnWebsiteSearchMappingWithoutAttributeType()
    {
        $event = new WebsiteSearchMappingEvent();

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

        $this->assertEquals([], $event->getConfiguration());
    }

    public function testOnWebsiteSearchMappingNotActiveAttribute()
    {
        $event = new WebsiteSearchMappingEvent();

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

        $this->assertEquals([], $event->getConfiguration());
    }

    public function testOnWebsiteSearchMappingSortableAttribute()
    {
        $event = $this->createEventWithBasicConfiguration();

        $attribute1 = new FieldConfigModel('attribute1');
        $attribute2 = new FieldConfigModel('attribute2');
        $attribute3 = new FieldConfigModel('attribute3');
        $attribute4 = new FieldConfigModel('attribute4');

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4]);

        $attributeType = new EnumSearchableAttributeType(new EnumAttributeType());

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
                    [$attribute4, false]
                ]
            );

        $this->configurationProvider->expects($this->any())
            ->method('isAttributeSortable')
            ->willReturnMap(
                [
                    [$attribute1, false],
                    [$attribute2, true],
                    [$attribute3, false],
                    [$attribute4, false]
                ]
            );

        $this->fieldsProvider->expects($this->any())
            ->method('isForceIndexed')
            ->willReturnMap(
                [
                    [$attribute1->getFieldName(), false],
                    [$attribute2->getFieldName(), false],
                    [$attribute3->getFieldName(), true],
                    [$attribute4->getFieldName(), false]
                ]
            );

        $this->listener->onWebsiteSearchMapping($event);

        $this->assertEquals(
            [
                Product::class => [
                    'fields' => [
                        'firstname' => [
                            'name' => 'firstname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'lasttname' => [
                            'name' => 'lasttname',
                            'type' => 'text',
                            'store' => true
                        ],
                        $attribute1->getFieldName() => [
                            'name' => $attribute1->getFieldName(),
                            'type' => Query::TYPE_TEXT
                        ],
                        $attribute2->getFieldName() . '_priority' => [
                            'name' => $attribute2->getFieldName() . '_priority',
                            'type' => Query::TYPE_INTEGER
                        ],
                        $attribute3->getFieldName() => [
                            'name' => $attribute3->getFieldName(),
                            'type' => Query::TYPE_TEXT
                        ],
                        $attribute3->getFieldName() . '_priority' => [
                            'name' => $attribute3->getFieldName() . '_priority',
                            'type' => Query::TYPE_INTEGER
                        ],
                    ]
                ],
                \stdClass::class => [
                    'fields' => []
                ]
            ],
            $event->getConfiguration()
        );
    }

    /**
     * @return WebsiteSearchMappingEvent
     */
    protected function createEventWithBasicConfiguration()
    {
        $event = new WebsiteSearchMappingEvent();
        $event->setConfiguration(
            [
                Product::class => [
                    'fields' => [
                        'firstname' => [
                            'name' => 'firstname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'lasttname' => [
                            'name' => 'lasttname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'attribute1' => [
                            'name' => 'test',
                            'type' => 'test'
                        ],
                    ]
                ],
                \stdClass::class => [
                    'fields' => []
                ]
            ]
        );

        return $event;
    }
}
