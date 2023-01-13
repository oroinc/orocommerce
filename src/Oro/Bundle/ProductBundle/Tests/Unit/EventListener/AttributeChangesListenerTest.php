<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\AttributeChangesListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AttributeChangesListenerTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'test_field';

    /** @var RequestStack */
    private $requestStack;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var AttributeChangesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->listener = new AttributeChangesListener($this->requestStack, $this->producer);

        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testPostFlushUnsupportedModel()
    {
        $this->producer->expects(self::never())
            ->method(self::anything());

        $model = new \stdClass();

        $this->listener->postFlush(new PostFlushConfigEvent([$model], $this->configManager));

        $attributeChangeSet = ['searchable' => [false, false]];

        self::assertTrue(isset($attributeChangeSet['searchable'][1]));
    }

    public function testPostFlushUnsupportedModelEntityClass()
    {
        $this->producer->expects(self::never())
            ->method(self::anything());

        $model = $this->getFieldConfigModel(\stdClass::class);

        $this->listener->postFlush(new PostFlushConfigEvent([$model], $this->configManager));
    }

    public function testPostFlushWithoutRequest()
    {
        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->postFlush(new PostFlushConfigEvent([new FieldConfigModel()], $this->configManager));
    }

    public function testPreFlushAfterImportAttribute()
    {
        $fieldConfig = $this->createMock(FieldConfigId::class);
        $fieldConfig->expects(self::once())
            ->method('getFieldName')
            ->willReturn('fieldName');
        $fieldConfig->expects(self::once())
            ->method('getClassName')
            ->willReturn(Product::class);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn($fieldConfig);
        $config->expects(self::once())
            ->method('get')
            ->with('request_search_indexation')
            ->willReturn(true);

        $configs = ['attribute' => $config];

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
    }

    public function testPreFlushAfterImportAttributeWrongClass()
    {
        $fieldConfig = $this->createMock(\stdClass::class);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('getId')
            ->willReturn($fieldConfig);
        $config->expects(self::never())
            ->method('get');

        $configs = ['attribute' => $config];

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
    }

    public function testPreFlushAfterImportAttributeNotProduct()
    {
        $fieldConfig = $this->createMock(FieldConfigId::class);
        $fieldConfig->expects(self::once())
            ->method('getClassName')
            ->willReturn(\stdClass::class);
        $fieldConfig->expects(self::never())
            ->method('getFieldName');

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn($fieldConfig);
        $config->expects(self::never())
            ->method('get');

        $configs = ['attribute' => $config];

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
    }

    public function testPreFlushAfterImportAttributeWithoutAttributeMarker()
    {
        $fieldConfig = $this->createMock(FieldConfigId::class);
        $fieldConfig->expects(self::once())
            ->method('getClassName')
            ->willReturn(Product::class);
        $fieldConfig->expects(self::never())
            ->method('getFieldName');

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn($fieldConfig);
        $config->expects(self::once())
            ->method('get')
            ->with('request_search_indexation')
            ->willReturn(false);

        $configs = ['attribute' => $config];

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));
    }

    /**
     * @dataProvider postFlushDataProvider
     */
    public function testPostFlush(
        InvokedCount $expected,
        array $extendConfigValues = [],
        array $extendChangeSet = [],
        array $attributeConfigValues = [],
        array $attributeChangeSet = [],
        array $frontendConfigValues = [],
        array $frontendChangeSet = []
    ): void {
        $this->requestStack->push(new Request());

        $this->setUpConfigManager(
            $extendConfigValues,
            $extendChangeSet,
            $attributeConfigValues,
            $attributeChangeSet,
            $frontendConfigValues,
            $frontendChangeSet
        );

        $model = $this->getFieldConfigModel(Product::class);

        $this->producer->expects($expected)
            ->method('send')
            ->with(ReindexProductsByAttributesTopic::getName(), ['attributeIds' => [1]]);

        $this->listener->postFlush(new PostFlushConfigEvent([$model], $this->configManager));
    }

    /**
     * @dataProvider postFlushDataProvider
     */
    public function testPostFlushAfterImport(
        InvokedCount $expected,
        array $extendConfigValues = [],
        array $extendChangeSet = [],
        array $attributeConfigValues = [],
        array $attributeChangeSet = [],
        array $frontendConfigValues = [],
        array $frontendChangeSet = []
    ): void {
        $fieldConfig = $this->createMock(FieldConfigId::class);
        $fieldConfig->expects(self::once())
            ->method('getFieldName')
            ->willReturn('test_field');
        $fieldConfig->expects(self::once())
            ->method('getClassName')
            ->willReturn(Product::class);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn($fieldConfig);
        $config->expects(self::once())
            ->method('get')
            ->with('request_search_indexation')
            ->willReturn(true);

        $configs = ['attribute' => $config];

        $this->listener->preFlush(new PreFlushConfigEvent($configs, $this->configManager));

        $this->setUpConfigManager(
            $extendConfigValues,
            $extendChangeSet,
            $attributeConfigValues,
            $attributeChangeSet,
            $frontendConfigValues,
            $frontendChangeSet
        );

        $model = $this->getFieldConfigModel(Product::class);

        $this->producer->expects($expected)
            ->method('send')
            ->with(ReindexProductsByAttributesTopic::getName(), ['attributeIds' => [1]]);

        $this->listener->postFlush(new PostFlushConfigEvent([$model], $this->configManager));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postFlushDataProvider(): \Generator
    {
        yield 'state not active and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_NEW]
        ];

        yield 'state changed from not active to not active' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE]]
        ];
        //searchable
        yield 'state changed from active to not active, searchable and not changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['searchable' => true]
        ];

        yield 'state changed from active to not active, searchable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['searchable' => true],
            'attributeChangeSet' => ['searchable' => [false, true]]
        ];

        yield 'state changed from active to not active, not searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['searchable' => false]
        ];

        yield 'state changed from active to not active, not searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['searchable' => false],
            'attributeChangeSet' => ['searchable' => [true, false]]
        ];
        // ----
        yield 'state changed from not active to active, searchable and not changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['searchable' => true]
        ];

        yield 'state changed from not active to active, searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['searchable' => true],
            'attributeChangeSet' => ['searchable' => [false, true]]
        ];

        yield 'state changed from not active to active, not searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['searchable' => false]
        ];

        yield 'state changed from not active to active, not searchable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['searchable' => false],
            'attributeChangeSet' => ['searchable' => [true, false]]
        ];
        // ----
        yield 'state active and not changed, searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true]
        ];

        yield 'state active and not changed, searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true],
            'attributeChangeSet' => ['searchable' => [false, true]]
        ];

        yield 'state active and not changed, not searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false]
        ];

        yield 'state active and not changed, not searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false],
            'attributeChangeSet' => ['searchable' => [true, false]]
        ];
        // ----
        yield 'state update and not changed, searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true]
        ];

        yield 'state update and not changed, searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true],
            'attributeChangeSet' => ['searchable' => [false, true]]
        ];

        yield 'state update and not changed, not searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false]
        ];

        yield 'state update and not changed, not searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false],
            'attributeChangeSet' => ['searchable' => [true, false]]
        ];
        // ----
        yield 'state changed from active to update, searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true]
        ];

        yield 'state changed from active to update, searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true],
            'attributeChangeSet' => ['searchable' => [false, true]]
        ];

        yield 'state changed from active to update, not searchable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false]
        ];

        yield 'state changed from active to update, not searchable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false],
            'attributeChangeSet' => ['searchable' => [true, false]]
        ];
        //search_boost
        yield 'state active and not changed, searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true]
        ];
        yield 'state active and not changed, searchable, boost changed from null' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state active and not changed, searchable, boost changed from 0' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state active and not changed, searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state active and not changed, searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state active and not changed, searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        yield 'state active and not changed, not searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false]
        ];
        yield 'state active and not changed, not searchable, boost changed from null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state active and not changed, not searchable, boost changed from 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state active and not changed, not searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state active and not changed, not searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state active and not changed, not searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        // ----
        yield 'state update and not changed, searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true]
        ];
        yield 'state update and not changed, searchable, boost changed from null' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state update and not changed, searchable, boost changed from 0' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state update and not changed, searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state update and not changed, searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state update and not changed, searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        yield 'state update and not changed, not searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false]
        ];
        yield 'state update and not changed, not searchable, boost changed from null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state update and not changed, not searchable, boost changed from 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state update and not changed, not searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state update and not changed, not searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state update and not changed, not searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        // ----
        yield 'state changed from active to update, searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true]
        ];
        yield 'state changed from active to update, searchable, boost changed from null' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state changed from active to update, searchable, boost changed from 0' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state changed from active to update, searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state changed from active to update, searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state changed from active to update, searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => true, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        yield 'state changed from active to update, not searchable, boost not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false]
        ];
        yield 'state changed from active to update, not searchable, boost changed from null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [null, 1.0]]
        ];
        yield 'state changed from active to update, not searchable, boost changed from 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0, 1.0]]
        ];
        yield 'state changed from active to update, not searchable, boost changed from not empty value' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 1.0],
            'attributeChangeSet' => ['search_boost' => [0.1, 1.0]]
        ];
        yield 'state changed from active to update, not searchable, boost changed to null' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => null],
            'attributeChangeSet' => ['search_boost' => [1.0, null]]
        ];
        yield 'state changed from active to update, not searchable, boost changed to 0' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['searchable' => false, 'search_boost' => 0],
            'attributeChangeSet' => ['search_boost' => [1.0, 0]]
        ];
        //filterable
        yield 'state changed from active to not active, filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['filterable' => true]
        ];

        yield 'state changed from active to not active, filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['filterable' => true],
            'attributeChangeSet' => ['filterable' => [false, true]]
        ];

        yield 'state changed from active to not active, not filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['filterable' => false]
        ];

        yield 'state changed from active to not active, not filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['filterable' => false],
            'attributeChangeSet' => ['filterable' => [true, false]]
        ];
        // ----
        yield 'state changed from not active to active, filterable and not changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['filterable' => true]
        ];

        yield 'state changed from not active to active, filterable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['filterable' => true],
            'attributeChangeSet' => ['filterable' => [false, true]]
        ];

        yield 'state changed from not active to active, not filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['filterable' => false]
        ];

        yield 'state changed from not active to active, not filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['filterable' => false],
            'attributeChangeSet' => ['filterable' => [true, false]]
        ];
        // ----
        yield 'state active and not changed, filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => true]
        ];

        yield 'state active and not changed, filterable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => true],
            'attributeChangeSet' => ['filterable' => [false, true]]
        ];

        yield 'state active and not changed, not filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => false]
        ];

        yield 'state active and not changed, not filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => false],
            'attributeChangeSet' => ['filterable' => [true, false]]
        ];
        // ----
        yield 'state update and not changed, filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => true]
        ];

        yield 'state update and not changed, filterable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => true],
            'attributeChangeSet' => ['filterable' => [false, true]]
        ];

        yield 'state update and not changed, not filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => false]
        ];

        yield 'state update and not changed, not filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['filterable' => false],
            'attributeChangeSet' => ['filterable' => [true, false]]
        ];
        // ----
        yield 'state changed from active to update, filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['filterable' => true]
        ];

        yield 'state changed from active to update, filterable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['filterable' => true],
            'attributeChangeSet' => ['filterable' => [false, true]]
        ];

        yield 'state changed from active to update, not filterable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['filterable' => false]
        ];

        yield 'state changed from active to update, not filterable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['filterable' => false],
            'attributeChangeSet' => ['filterable' => [true, false]]
        ];
        //sortable
        yield 'state changed from active to not active, sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['sortable' => true]
        ];

        yield 'state changed from active to not active, sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['sortable' => true],
            'attributeChangeSet' => ['sortable' => [false, true]]
        ];

        yield 'state changed from active to not active, not sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['sortable' => false]
        ];

        yield 'state changed from active to not active, not sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => ['sortable' => false],
            'attributeChangeSet' => ['sortable' => [true, false]]
        ];
        // ----
        yield 'state changed from not active to active, sortable and not changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['sortable' => true]
        ];

        yield 'state changed from not active to active, sortable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['sortable' => true],
            'attributeChangeSet' => ['sortable' => [false, true]]
        ];

        yield 'state changed from not active to active, not sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['sortable' => false]
        ];

        yield 'state changed from not active to active, not sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => ['sortable' => false],
            'attributeChangeSet' => ['sortable' => [true, false]]
        ];
        // ----
        yield 'state active and not changed, sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => true]
        ];

        yield 'state active and not changed, sortable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => true],
            'attributeChangeSet' => ['sortable' => [false, true]]
        ];

        yield 'state active and not changed, not sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => false]
        ];

        yield 'state active and not changed, not sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => false],
            'attributeChangeSet' => ['sortable' => [true, false]]
        ];
        // ----
        yield 'state update and not changed, sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => true]
        ];

        yield 'state update and not changed, sortable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => true],
            'attributeChangeSet' => ['sortable' => [false, true]]
        ];

        yield 'state update and not changed, not sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => false]
        ];

        yield 'state update and not changed, not sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => ['sortable' => false],
            'attributeChangeSet' => ['sortable' => [true, false]]
        ];
        // ----
        yield 'state changed from active to update, sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['sortable' => true]
        ];

        yield 'state changed from active to update, sortable and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['sortable' => true],
            'attributeChangeSet' => ['sortable' => [false, true]]
        ];

        yield 'state changed from active to update, not sortable and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['sortable' => false]
        ];

        yield 'state changed from active to update, not sortable and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => ['sortable' => false],
            'attributeChangeSet' => ['sortable' => [true, false]]
        ];
        //visible
        yield 'state changed from active to not active, visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true]
        ];

        yield 'state changed from active to not active, visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true],
            'frontendChangeSet' => ['is_displayable' => [false, true]]
        ];

        yield 'state changed from active to not active, not visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false]
        ];

        yield 'state changed from active to not active, not visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_DELETE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false],
            'frontendChangeSet' => ['is_displayable' => [true, false]]
        ];
        // ----
        yield 'state changed from not active to active, visible and not changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true]
        ];

        yield 'state changed from not active to active, visible and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true],
            'frontendChangeSet' => ['is_displayable' => [false, true]]
        ];

        yield 'state changed from not active to active, not visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false]
        ];

        yield 'state changed from not active to active, not visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_NEW, ExtendScope::STATE_ACTIVE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false],
            'frontendChangeSet' => ['is_displayable' => [true, false]]
        ];
        // ----
        yield 'state active and not changed, visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true]
        ];

        yield 'state active and not changed, visible and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true],
            'frontendChangeSet' => ['is_displayable' => [false, true]]
        ];

        yield 'state active and not changed, not visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false]
        ];

        yield 'state active and not changed, not visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_ACTIVE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false],
            'frontendChangeSet' => ['is_displayable' => [true, false]]
        ];
        // ----
        yield 'state update and not changed, visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true]
        ];

        yield 'state update and not changed, visible and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true],
            'frontendChangeSet' => ['is_displayable' => [false, true]]
        ];

        yield 'state update and not changed, not visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false]
        ];

        yield 'state update and not changed, not visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => [],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false],
            'frontendChangeSet' => ['is_displayable' => [true, false]]
        ];
        // ----
        yield 'state changed from active to update, visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true]
        ];

        yield 'state changed from active to update, visible and changed' => [
            'expected' => self::once(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => true],
            'frontendChangeSet' => ['is_displayable' => [false, true]]
        ];

        yield 'state changed from active to update, not visible and not changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false]
        ];

        yield 'state changed from active to update, not visible and changed' => [
            'expected' => self::never(),
            'extendConfigValues' => ['state' => ExtendScope::STATE_UPDATE],
            'extendChangeSet' => ['state' => [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]],
            'attributeConfigValues' => [],
            'attributeChangeSet' => [],
            'frontendConfigValues' => ['is_displayable' => false],
            'frontendChangeSet' => ['is_displayable' => [true, false]]
        ];
    }

    private function getFieldConfigModel(string $className): FieldConfigModel
    {
        $entityModel = new EntityConfigModel();
        $entityModel->setClassName($className);

        $fieldModel = new FieldConfigModel();
        $fieldModel->setFieldName(self::FIELD_NAME)->setEntity($entityModel);
        ReflectionUtil::setId($fieldModel, 1);

        return $fieldModel;
    }

    private function setUpConfigManager(
        array $extendConfigValues,
        array $extendChangeSet,
        array $attributeConfigValues,
        array $attributeChangeSet,
        array $frontendConfigValues,
        array $frontendChangeSet
    ): void {
        $extendConfigId = $this->createMock(ConfigIdInterface::class);
        $extendConfig = new Config($extendConfigId, $extendConfigValues);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects(self::any())
            ->method('getConfig')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($extendConfig);

        $attributeConfigId = $this->createMock(ConfigIdInterface::class);
        $attributeConfig = new Config($attributeConfigId, $attributeConfigValues);
        $frontendConfig = new Config($attributeConfigId, $frontendConfigValues);

        $attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $attributeConfigProvider->expects(self::any())
            ->method('getConfig')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($attributeConfig);

        $frontendConfigProvider = $this->createMock(ConfigProvider::class);
        $frontendConfigProvider->expects(self::any())
            ->method('getConfig')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($frontendConfig);

        $this->configManager->expects(self::any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['attribute', $attributeConfigProvider],
                ['frontend', $frontendConfigProvider]
            ]);
        $this->configManager->expects(self::any())
            ->method('getConfigChangeSet')
            ->willReturnMap([
                [$extendConfig, $extendChangeSet],
                [$attributeConfig, $attributeChangeSet],
                [$frontendConfig, $frontendChangeSet]
            ]);
    }
}
