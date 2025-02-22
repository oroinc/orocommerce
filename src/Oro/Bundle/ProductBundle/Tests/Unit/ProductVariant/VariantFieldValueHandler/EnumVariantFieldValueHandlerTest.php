<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumVariantFieldValueHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'fieldName';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var enumOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumOptionsProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumVariantFieldValueHandler */
    private $handler;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocaleSettings */
    private $localeSettings;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->handler = new EnumVariantFieldValueHandler(
            $this->doctrineHelper,
            $this->enumOptionsProvider,
            $this->logger,
            $this->configManager,
            $this->localizationHelper,
            $this->localeSettings
        );
    }

    public function testGetType()
    {
        $this->assertEquals(EnumVariantFieldValueHandler::TYPE, $this->handler->getType());
    }

    public function testGetValues()
    {
        $fieldName = 'testField';
        $enumValues = ['red', 'green'];

        $fieldConfig = $this->createMock(FieldConfigModel::class);
        $fieldConfig->expects(self::once())
            ->method('toArray')
            ->with('enum')
            ->willReturn(['target_entity' => EnumOption::class, 'enum_code' => 'test']);
        $this->configManager->expects(self::once())
            ->method('getConfigFieldModel')
            ->with(Product::class, $fieldName)
            ->willReturn($fieldConfig);

        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with('test')
            ->willReturn($enumValues);

        $localization = (new Localization())->setFormattingCode('en_US');
        $this->localizationHelper->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));

        //check array cache
        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));
    }

    public function testGetValuesWithCache(): void
    {
        $fieldName = 'testField';
        $enumValues = ['red', 'green'];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(sprintf('%s|%s', $fieldName, 'en_US'))
            ->willReturn($enumValues);

        $this->handler->setCache($cache);

        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn('en_US');

        $this->configManager->expects($this->never())
            ->method('getConfigFieldModel');

        $this->enumOptionsProvider->expects($this->never())
            ->method('getEnumChoicesByCode');

        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));
    }

    public function testGetScalarValue()
    {
        $fieldValue = new TestEnumValue('test_enum_code', 'Test', 'test1');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($fieldValue)
            ->willReturn($fieldValue->getId());

        $this->assertEquals('test_enum_code.test1', $this->handler->getScalarValue($fieldValue));
    }

    public function testGetScalarValueWithNonEnum()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->assertNull($this->handler->getScalarValue('1'));
    }

    public function testGetHumanReadableValueForEnumValue()
    {
        $fieldName = 'test_field';
        $fieldValue = new TestEnumValue('test_enum_code', 'Test', 'test1');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($fieldValue)
            ->willReturn($fieldValue->getId());

        $fieldConfigModel = new FieldConfigModel($fieldName);
        $fieldConfigModel->fromArray('enum', ['target_entity' => Product::class, 'enum_code' => 'test1']);

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, $fieldName)
            ->willReturn($fieldConfigModel);

        $this->enumOptionsProvider->expects($this->once())
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with('test1')
            ->willReturn(['test_enum_code.test1' => 'cache_data']);

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn('en_US');

        $this->assertEquals('cache_data', $this->handler->getHumanReadableValue($fieldName, $fieldValue));
    }

    public function testGetHumanReadableValueForScalarValue()
    {
        $fieldName = 'test_field';
        $fieldValue = 'test';

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->configManager->expects($this->never())
            ->method('getConfigFieldModel');

        $this->enumOptionsProvider->expects($this->never())
            ->method('getEnumChoicesWithNonUniqueTranslation');

        $this->localeSettings->expects($this->never())
            ->method('getLocale');

        $this->assertEquals('N/A', $this->handler->getHumanReadableValue($fieldName, $fieldValue));
    }

    public function testGetPossibleValues(): void
    {
        $fieldConfigModel = new FieldConfigModel(self::FIELD_NAME);
        $fieldConfigModel->fromArray('enum', ['target_entity' => Product::class, 'enum_code' => 'test2']);

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $this->enumOptionsProvider->expects($this->once())
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with('test2')
            ->willReturn(['cache_data']);

        $this->localeSettings->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('en_US');

        $expected = $this->handler->getPossibleValues('fieldName');
        // Cache fetch
        $actual = $this->handler->getPossibleValues('fieldName');
        $this->assertEquals($expected, $actual);
    }

    public function testGetEmptyPossibleValues(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn(null);

        $this->enumOptionsProvider
            ->expects($this->never())
            ->method('getEnumChoicesWithNonUniqueTranslation');

        $this->localeSettings
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('en_US');

        $expected = $this->handler->getPossibleValues('fieldName');
        // Cache fetch
        $actual = $this->handler->getPossibleValues('fieldName');
        $this->assertEmpty($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetPossibleValuesWithDifferenceLocale(): void
    {
        $fieldConfigModel = new FieldConfigModel(self::FIELD_NAME);
        $fieldConfigModel->fromArray('enum', ['target_entity' => Product::class, 'enum_code' => 'test3']);

        $this->configManager->expects($this->exactly(2))
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $this->enumOptionsProvider->expects($this->exactly(2))
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with('test3')
            ->willReturnOnConsecutiveCalls(['cache_data_en'], ['cache_data_de']);

        $this->localeSettings->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturnOnConsecutiveCalls('en_US', 'de_DE');

        $enData = $this->handler->getPossibleValues('fieldName');
        $deData = $this->handler->getPossibleValues('fieldName');

        $this->assertEquals(['cache_data_en'], $enData);
        $this->assertEquals(['cache_data_de'], $deData);
    }

    public function testGetHumanReadableValueWithLoggedError(): void
    {
        $fieldConfigModel = new FieldConfigModel(self::FIELD_NAME);
        $fieldConfigModel->fromArray('enum', ['target_entity' => Product::class, 'enum_code' => 'test4']);

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $availableValues = ['cache_data_en'];
        $this->enumOptionsProvider->expects($this->once())
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with('test4')
            ->willReturnOnConsecutiveCalls($availableValues);

        $this->localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturnOnConsecutiveCalls('en_US');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn('1');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Can not find configurable attribute "{attribute}" in list of available attributes.' .
                'Available: "{availableAttributes}"',
                [
                    'attribute' => '1',
                    'availableAttributes' => implode(', ', array_keys([$availableValues])),
                ]
            );

        $enData = $this->handler->getHumanReadableValue(self::FIELD_NAME, new TestEnumValue('test', 'Test', 'test1'));

        $this->assertEquals('N/A', $enData);
    }
}
