<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType as DBALWYSIWYGType;
use Oro\Bundle\CMSBundle\EventListener\WYSIWYGSerializedConfigListener;
use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class WYSIWYGSerializedConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGSerializedConfigListener */
    private $listener;

    /** @var WYSIWYGSchemaHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygSchemaHelper;

    protected function setUp(): void
    {
        $this->wysiwygSchemaHelper = $this->createMock(WYSIWYGSchemaHelper::class);
        $this->listener = new WYSIWYGSerializedConfigListener($this->wysiwygSchemaHelper);
    }

    public function testPreFlushWithNewConfig(): void
    {
        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn(DBALWYSIWYGType::TYPE);
        $fieldConfigId
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(TestActivityTarget::class);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_serialized')
            ->willReturn(true);
        $fieldConfig
            ->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($fieldConfigId);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn($entityConfig);

        $this->wysiwygSchemaHelper
            ->expects($this->once())
            ->method('createStyleField')
            ->with($entityConfig, $fieldConfig);

        $preFlushConfigEvent = new PreFlushConfigEvent(['extend' => $fieldConfig], $configManager);
        $this->listener->preFlush($preFlushConfigEvent);
    }
}
