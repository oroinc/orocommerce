<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Tools\DumperExtension;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\CMSBundle\Tools\DumperExtensions\WYSIWYGEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class WYSIWYGEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGEntityConfigDumperExtension */
    private $extension;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WYSIWYGSchemaHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygSchemaHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->wysiwygSchemaHelper = $this->createMock(WYSIWYGSchemaHelper::class);
        $this->extension = new WYSIWYGEntityConfigDumperExtension($this->configManager, $this->wysiwygSchemaHelper);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE));
        $this->assertTrue($this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE));
        $this->assertFalse($this->extension->supports('not_valid_action'));
    }

    public function testPreUpdate(): void
    {
        /** @var ConfigIdInterface|\PHPUnit\Framework\MockObject\MockObject $entityConfigId */
        $entityConfigId = $this->createMock(EntityConfigId::class);
        $entityConfigId
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(TestActivityTarget::class);
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $entityConfig */
        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->willReturn(true);
        $entityConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($entityConfigId);

        /** @var ConfigIdInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfigId */
        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn(WYSIWYGType::TYPE);
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfig */
        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider
            ->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnOnConsecutiveCalls([$entityConfig], [$fieldConfig]);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);

        $this->extension->preUpdate();
        $configs = new \ReflectionProperty($this->extension, 'configs');
        $configs->setAccessible(true);
        $this->assertEquals(
            ['entityConfig' => $entityConfig, 'fieldConfig' => $fieldConfig],
            $configs->getValue($this->extension)[0]
        );
    }

    public function testPostUpdate(): void
    {
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $entityConfig */
        $entityConfig = $this->createMock(ConfigInterface::class);
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfig */
        $fieldConfig = $this->createMock(ConfigInterface::class);

        $configs = new \ReflectionProperty($this->extension, 'configs');
        $configs->setAccessible(true);
        $configs->setValue(
            $this->extension,
            [
                ['entityConfig' => $entityConfig, 'fieldConfig' => $fieldConfig],
                ['entityConfig' => $entityConfig, 'fieldConfig' => $fieldConfig],
            ]
        );

        $this->wysiwygSchemaHelper
            ->expects($this->exactly(2))
            ->method('createStyleField')
            ->with($entityConfig, $fieldConfig);

        $this->extension->postUpdate();
    }
}
