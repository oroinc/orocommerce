<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductVariant;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;

class EnumVariantFieldValueHandlerTest extends WebTestCase
{
    private const FIELD_NAME = 'fieldName';

    /** @var EnumVariantFieldValueHandler  */
    private $enumVarianFieldValueHandler;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumValueProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CacheProvider */
    private $cache;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    public function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->cache = $this->getCache();

        $this->enumVarianFieldValueHandler = new EnumVariantFieldValueHandler(
            $this->doctrineHelper,
            $this->enumValueProvider,
            $this->logger,
            $this->configManager
        );
        $this->enumVarianFieldValueHandler->setLocaleSettings($this->localeSettings);
        $this->enumVarianFieldValueHandler->setLocalizationHelper($this->localizationHelper);
    }

    public function testGetPossibleValues(): void
    {
        $fieldConfigModel = new FieldConfigModel(self::FIELD_NAME);
        $fieldConfigModel->fromArray('extend', ['target_entity' => Product::class]);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $this->enumValueProvider
            ->expects($this->once())
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with(Product::class)
            ->willReturn(['cache_data']);

        $this->localeSettings
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('en_US');

        $expected = $this->enumVarianFieldValueHandler->getPossibleValues('fieldName');
        // Cache fetch
        $actual = $this->enumVarianFieldValueHandler->getPossibleValues('fieldName');
        $this->assertEquals($expected, $actual);
    }

    public function testGetPossibleValuesWithDifferenceLocale(): void
    {
        $fieldConfigModel = new FieldConfigModel(self::FIELD_NAME);
        $fieldConfigModel->fromArray('extend', ['target_entity' => Product::class]);

        $this->configManager
            ->expects($this->exactly(2))
            ->method('getConfigFieldModel')
            ->with(Product::class, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $this->enumValueProvider
            ->expects($this->exactly(2))
            ->method('getEnumChoicesWithNonUniqueTranslation')
            ->with(Product::class)
            ->willReturnOnConsecutiveCalls(['cache_data_en'], ['cache_data_de']);

        $this->localeSettings
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturnOnConsecutiveCalls('en_US', 'de_DE');

        $enData = $this->enumVarianFieldValueHandler->getPossibleValues('fieldName');
        $deData = $this->enumVarianFieldValueHandler->getPossibleValues('fieldName');

        $this->assertEquals(['cache_data_en'], $enData);
        $this->assertEquals(['cache_data_de'], $deData);
    }

    /**
     * @return Cache|object
     */
    private function getCache()
    {
        return $this
            ->getContainer()
            ->get('oro_product.product_variant_field.field_value_handler.enum_type_handler.cache');
    }
}
