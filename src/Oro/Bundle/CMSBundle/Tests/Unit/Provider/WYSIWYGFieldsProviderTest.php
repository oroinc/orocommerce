<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WYSIWYGFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WYSIWYGFieldsProvider */
    private $wysiwygFieldsProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->wysiwygFieldsProvider = new WYSIWYGFieldsProvider($this->doctrine, $this->configManager);
    }

    private function getFieldConfig(
        string $fieldName,
        string $fieldType,
        array $properties,
        string $scope = 'extend'
    ): Config {
        return new Config(
            new FieldConfigId($scope, self::ENTITY_CLASS, $fieldName, $fieldType),
            $properties
        );
    }

    public function testGetWysiwygFieldsForNonManageableEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        self::assertSame(
            [],
            $this->wysiwygFieldsProvider->getWysiwygFields(self::ENTITY_CLASS)
        );
    }

    public function testGetWysiwygFieldsForNonConfigurableEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['wysiwygField', 'stringField']);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['stringField', 'string']
            ]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::isNull())
            ->willReturn(false);

        self::assertSame(
            ['wysiwygField'],
            $this->wysiwygFieldsProvider->getWysiwygFields(self::ENTITY_CLASS)
        );
    }

    public function testGetWysiwygFieldsForConfigurableEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::isNull())
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getIds')
            ->with('extend', self::ENTITY_CLASS, self::isTrue())
            ->willReturn([
                new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygField', WYSIWYGType::TYPE),
                new FieldConfigId('extend', self::ENTITY_CLASS, 'stringField', 'string')
            ]);

        self::assertEquals(
            ['wysiwygField'],
            $this->wysiwygFieldsProvider->getWysiwygFields(self::ENTITY_CLASS)
        );
    }

    public function testIsSerializedWysiwygFieldForNonManageableEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        self::assertFalse(
            $this->wysiwygFieldsProvider->isSerializedWysiwygField(self::ENTITY_CLASS, 'field')
        );
    }

    public function testIsSerializedWysiwygFieldForNonConfigurableEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'field')
            ->willReturn(false);

        self::assertFalse(
            $this->wysiwygFieldsProvider->isSerializedWysiwygField(self::ENTITY_CLASS, 'field')
        );
    }

    public function testIsSerializedWysiwygFieldForNotSerialisedField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'field')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, 'field')
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, 'field', []));

        self::assertFalse(
            $this->wysiwygFieldsProvider->isSerializedWysiwygField(self::ENTITY_CLASS, 'field')
        );
    }

    public function testIsSerializedWysiwygFieldForSerialisedField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'field')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, 'field')
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, 'field', ['is_serialized' => true]));

        self::assertTrue(
            $this->wysiwygFieldsProvider->isSerializedWysiwygField(self::ENTITY_CLASS, 'field')
        );
    }

    public function testGetWysiwygAttributesForNonManageableEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        self::assertSame(
            [],
            $this->wysiwygFieldsProvider->getWysiwygAttributes(self::ENTITY_CLASS)
        );
    }

    public function testGetWysiwygAttributesForNonConfigurableEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['wysiwygField', 'wysiwygAttribute', 'stringField']);
        $metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygAttribute', WYSIWYGType::TYPE],
                ['stringField', 'string']
            ]);

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [self::ENTITY_CLASS, null, false],
                [self::ENTITY_CLASS, 'wysiwygField', false],
                [self::ENTITY_CLASS, 'wysiwygAttribute', false]
            ]);
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        self::assertSame(
            [],
            $this->wysiwygFieldsProvider->getWysiwygAttributes(self::ENTITY_CLASS)
        );
    }

    public function testGetWysiwygAttributesForConfigurableEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::never())
            ->method('getClassMetadata');

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [self::ENTITY_CLASS, null, true],
                [self::ENTITY_CLASS, 'wysiwygField', true],
                [self::ENTITY_CLASS, 'wysiwygAttribute', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getIds')
            ->with('extend', self::ENTITY_CLASS, self::isTrue())
            ->willReturn([
                new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygField', WYSIWYGType::TYPE),
                new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygAttribute', WYSIWYGType::TYPE),
                new FieldConfigId('extend', self::ENTITY_CLASS, 'stringField', 'string')
            ]);
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'attribute',
                    self::ENTITY_CLASS,
                    'wysiwygField',
                    $this->getFieldConfig('wysiwygField', WYSIWYGType::TYPE, ['is_attribute' => false], 'attribute')
                ],
                [
                    'attribute',
                    self::ENTITY_CLASS,
                    'wysiwygAttribute',
                    $this->getFieldConfig('wysiwygField', WYSIWYGType::TYPE, ['is_attribute' => true], 'attribute')
                ]
            ]);

        self::assertEquals(
            ['wysiwygAttribute'],
            $this->wysiwygFieldsProvider->getWysiwygAttributes(self::ENTITY_CLASS)
        );
    }

    public function testIsWysiwygAttributeForNotAttribute()
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'field')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attribute', self::ENTITY_CLASS, 'field')
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, 'field', [], 'attribute'));

        self::assertFalse(
            $this->wysiwygFieldsProvider->isWysiwygAttribute(self::ENTITY_CLASS, 'field')
        );
    }

    public function testIsWysiwygAttributeForAttribute()
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'field')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attribute', self::ENTITY_CLASS, 'field')
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, 'field', ['is_attribute' => true], 'attribute'));

        self::assertTrue(
            $this->wysiwygFieldsProvider->isWysiwygAttribute(self::ENTITY_CLASS, 'field')
        );
    }

    public function testGetWysiwygStyleField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_style', true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygField_style', WYSIWYGStyleType::TYPE]
            ]);

        self::assertSame(
            'wysiwygField_style',
            $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }

    public function testGetWysiwygStyleFieldWhenItsNameIsCamelized()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_style', false],
                ['wysiwygFieldStyle', true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygFieldStyle', WYSIWYGStyleType::TYPE]
            ]);

        self::assertSame(
            'wysiwygFieldStyle',
            $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }

    public function testGetWysiwygStyleFieldWhenItDoesNotExist()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_style', false],
                ['wysiwygFieldStyle', false]
            ]);
        $metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('wysiwygField')
            ->willReturn(WYSIWYGType::TYPE);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "style" field for the "Test\Entity::wysiwygField" WYSIWYG field was not found.'
        );

        $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygStyleFieldWhenItHasUnexpectedType()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_style', true],
                ['wysiwygFieldStyle', false]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygField_style', 'string']
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "style" field for the "Test\Entity::wysiwygField" WYSIWYG field was not found.'
        );

        $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygStyleFieldForNotWysiwygField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::once())
            ->method('hasField')
            ->with('wysiwygField')
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('wysiwygField')
            ->willReturn('string');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The field "Test\Entity::wysiwygField" is not WYSIWYG field.');

        $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygStyleFieldForNonManageableEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "Test\Entity" is non manageable entity.');

        $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygStyleFieldForSerializedField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', false],
                ['wysiwygField_style', false]
            ]);
        $metadata->expects(self::never())
            ->method('getTypeOfField');

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::ENTITY_CLASS, 'wysiwygField', true],
                [self::ENTITY_CLASS, 'wysiwygField_style', true]
            ]);
        $this->configManager->expects(self::exactly(2))
            ->method('getId')
            ->willReturnMap([
                [
                    'extend',
                    self::ENTITY_CLASS,
                    'wysiwygField',
                    new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygField', WYSIWYGType::TYPE)
                ],
                [
                    'extend',
                    self::ENTITY_CLASS,
                    'wysiwygField_style',
                    new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygField_style', WYSIWYGStyleType::TYPE)
                ]
            ]);

        self::assertSame(
            'wysiwygField_style',
            $this->wysiwygFieldsProvider->getWysiwygStyleField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }

    public function testGetWysiwygPropertiesField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_properties', true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygField_properties', WYSIWYGPropertiesType::TYPE]
            ]);

        self::assertSame(
            'wysiwygField_properties',
            $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }

    public function testGetWysiwygPropertiesFieldWhenItsNameIsCamelized()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_properties', false],
                ['wysiwygFieldProperties', true]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygFieldProperties', WYSIWYGPropertiesType::TYPE]
            ]);

        self::assertSame(
            'wysiwygFieldProperties',
            $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }

    public function testGetWysiwygPropertiesFieldWhenItDoesNotExist()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_properties', false],
                ['wysiwygFieldProperties', false]
            ]);
        $metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('wysiwygField')
            ->willReturn(WYSIWYGType::TYPE);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "properties" field for the "Test\Entity::wysiwygField" WYSIWYG field was not found.'
        );

        $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygPropertiesFieldWhenItHasUnexpectedType()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', true],
                ['wysiwygField_properties', true],
                ['wysiwygFieldProperties', false]
            ]);
        $metadata->expects(self::exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['wysiwygField', WYSIWYGType::TYPE],
                ['wysiwygField_properties', 'string']
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "properties" field for the "Test\Entity::wysiwygField" WYSIWYG field was not found.'
        );

        $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygPropertiesFieldForNotWysiwygField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::once())
            ->method('hasField')
            ->with('wysiwygField')
            ->willReturn(true);
        $metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('wysiwygField')
            ->willReturn('string');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The field "Test\Entity::wysiwygField" is not WYSIWYG field.');

        $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygPropertiesFieldForNonManageableEntity()
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "Test\Entity" is non manageable entity.');

        $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField');
    }

    public function testGetWysiwygPropertiesFieldForSerializedField()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn(self::ENTITY_CLASS);
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
                ['wysiwygField', false],
                ['wysiwygField_properties', false]
            ]);
        $metadata->expects(self::never())
            ->method('getTypeOfField');

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::ENTITY_CLASS, 'wysiwygField', true],
                [self::ENTITY_CLASS, 'wysiwygField_properties', true]
            ]);
        $this->configManager->expects(self::exactly(2))
            ->method('getId')
            ->willReturnMap([
                [
                    'extend',
                    self::ENTITY_CLASS,
                    'wysiwygField',
                    new FieldConfigId('extend', self::ENTITY_CLASS, 'wysiwygField', WYSIWYGType::TYPE)
                ],
                [
                    'extend',
                    self::ENTITY_CLASS,
                    'wysiwygField_properties',
                    new FieldConfigId(
                        'extend',
                        self::ENTITY_CLASS,
                        'wysiwygField_properties',
                        WYSIWYGPropertiesType::TYPE
                    )
                ]
            ]);

        self::assertSame(
            'wysiwygField_properties',
            $this->wysiwygFieldsProvider->getWysiwygPropertiesField(self::ENTITY_CLASS, 'wysiwygField')
        );
    }
}
