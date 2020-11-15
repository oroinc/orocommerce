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

    /**
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $properties
     *
     * @return Config
     */
    private function getFieldConfig(string $fieldName, string $fieldType, array $properties): Config
    {
        return new Config(
            new FieldConfigId('extend', self::ENTITY_CLASS, $fieldName, $fieldType),
            $properties
        );
    }

    public function testGetWysiwygFields()
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

        self::assertEquals(
            ['wysiwygField'],
            $this->wysiwygFieldsProvider->getWysiwygFields(self::ENTITY_CLASS)
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

    public function testGetWysiwygAttributes()
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

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::ENTITY_CLASS, 'wysiwygField', true],
                [self::ENTITY_CLASS, 'wysiwygAttribute', true]
            ]);
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'attribute',
                    self::ENTITY_CLASS,
                    'wysiwygField',
                    $this->getFieldConfig('wysiwygField', WYSIWYGType::TYPE, ['is_attribute' => false])
                ],
                [
                    'attribute',
                    self::ENTITY_CLASS,
                    'wysiwygAttribute',
                    $this->getFieldConfig('wysiwygField', WYSIWYGType::TYPE, ['is_attribute' => true])
                ]
            ]);

        self::assertEquals(
            ['wysiwygAttribute'],
            $this->wysiwygFieldsProvider->getWysiwygAttributes(self::ENTITY_CLASS)
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

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
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
        $metadata->expects(self::once())
            ->method('hasField')
            ->with('wysiwygField_style')
            ->willReturn(true);
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::never())
            ->method('hasField');
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
        $metadata->expects(self::once())
            ->method('hasField')
            ->with('wysiwygField_properties')
            ->willReturn(true);
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::exactly(2))
            ->method('hasField')
            ->willReturnMap([
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
        $metadata->expects(self::never())
            ->method('hasField');
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
}
