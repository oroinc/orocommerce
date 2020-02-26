<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class WYSIWYGFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = '\stdClass';

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var WYSIWYGFieldsProvider
     */
    private $wysiwygFieldsProvider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->wysiwygFieldsProvider = new WYSIWYGFieldsProvider($this->configManager);
    }

    public function testGetWysiwygFields()
    {
        $entityConfigModel = new EntityConfigModel(self::ENTITY_CLASS);
        $entityConfigModel->setFields(new ArrayCollection([
            $this->createFieldConfigModel('wysiwygField', 'wysiwyg'),
            $this->createFieldConfigModel('stringField', 'string')
        ]));

        $this->configManager->expects($this->once())
            ->method('getConfigEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($entityConfigModel);

        $this->assertEquals(
            ['wysiwygField'],
            $this->wysiwygFieldsProvider->getWysiwygFields(self::ENTITY_CLASS)
        );
    }

    public function testGetWysiwygAttributes()
    {
        $entityConfigModel = new EntityConfigModel(self::ENTITY_CLASS);
        $entityConfigModel->setFields(new ArrayCollection([
            $this->createFieldConfigModel('wysiwygField', 'wysiwyg'),
            $this->createFieldConfigModel('wysiwygAttributeField', 'wysiwyg')
        ]));

        $fieldConfig = $this->createFieldConfig('wysiwygField', 'wysiwyg', ['is_attribute' => false]);
        $attributeFieldConfig = $this->createFieldConfig(
            'wysiwygAttributeField',
            'wysiwyg',
            ['is_attribute' => true]
        );

        $this->configManager->expects($this->any())
            ->method('getFieldConfig')
            ->willReturnMap([
                ['attribute', self::ENTITY_CLASS, 'wysiwygField', $fieldConfig],
                ['attribute', self::ENTITY_CLASS, 'wysiwygAttributeField', $attributeFieldConfig]
            ]);

        $this->configManager->expects($this->once())
            ->method('getConfigEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($entityConfigModel);

        $this->assertEquals(
            ['wysiwygAttributeField'],
            $this->wysiwygFieldsProvider->getWysiwygAttributes(self::ENTITY_CLASS)
        );
    }

    /**
     * @param string $fieldName
     * @param string $fieldType
     * @return FieldConfigModel
     */
    private function createFieldConfigModel(string $fieldName, string $fieldType): FieldConfigModel
    {
        return new FieldConfigModel($fieldName, $fieldType);
    }

    /**
     * @param string $fieldName
     * @param string $fieldType
     * @param array $properties
     * @return Config
     */
    private function createFieldConfig(string $fieldName, string $fieldType, array $properties): Config
    {
        return new Config(
            new FieldConfigId('extend', self::ENTITY_CLASS, $fieldName, $fieldType),
            $properties
        );
    }
}
