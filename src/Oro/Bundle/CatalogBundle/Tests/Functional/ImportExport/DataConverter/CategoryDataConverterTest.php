<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\DataConverter\CategoryDataConverter;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryDataConverterTest extends WebTestCase
{
    public const ALL_FIELDS = [
        'titles.default.fallback' => '',
        'titles.default.value' => '',
        'id' => '',
        'titles.English (United States).fallback' => '',
        'titles.English (United States).value' => '',
        'parentCategory.id' => '',
        'parentCategory.title' => '',
        'shortDescriptions.default.fallback' => '',
        'shortDescriptions.default.value' => '',
        'shortDescriptions.English (United States).fallback' => '',
        'shortDescriptions.English (United States).value' => '',
        'longDescriptions.default.fallback' => '',
        'longDescriptions.default.value' => '',
        'longDescriptions.English (United States).fallback' => '',
        'longDescriptions.English (United States).value' => '',
        'metaDescriptions.default.fallback' => '',
        'metaDescriptions.default.value' => '',
        'metaDescriptions.English (United States).fallback' => '',
        'metaDescriptions.English (United States).value' => '',
        'metaKeywords.default.fallback' => '',
        'metaKeywords.default.value' => '',
        'metaKeywords.English (United States).fallback' => '',
        'metaKeywords.English (United States).value' => '',
        'metaTitles.default.fallback' => '',
        'metaTitles.default.value' => '',
        'metaTitles.English (United States).fallback' => '',
        'metaTitles.English (United States).value' => '',
        'slugPrototypes.default.fallback' => '',
        'slugPrototypes.default.value' => '',
        'slugPrototypes.English (United States).fallback' => '',
        'slugPrototypes.English (United States).value' => '',
    ];

    /** @var CategoryDataConverter */
    private $converter;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->initClient();

        $container = $this->getContainer();

        $this->converter = new CategoryDataConverter(
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator'),
            $container->get('oro_locale.settings')
        );
        $this->converter->setEntityName(Category::class);
        $this->converter->setDispatcher($container->get('event_dispatcher'));
        $this->converter->setRegistry($container->get('doctrine'));
        $this->converter->setLocalizedFallbackValueClassName(AbstractLocalizedFallbackValue::class);
        $this->converter->setLocalizationClassName(Localization::class);
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected): void
    {
        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    public function exportDataProvider(): array
    {
        return [
            'parent category title is added' => [
                [],
                self::ALL_FIELDS,
            ],
            'parent category title is converted' => [
                ['parentCategory' => ['titles' => ['default' => ['string' => 'sample title']]]],
                array_merge(self::ALL_FIELDS, ['parentCategory.title' => 'sample title']),
            ],
            'organization column is not added' => [
                [],
                self::ALL_FIELDS,
            ],
        ];
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected): void
    {
        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    public function importDataProvider(): array
    {
        return [
            'organization column is absent' => [
                [],
                [],
            ],
            'organization column is not converted' => [
                ['organization.name' => 'sample organization'],
                ['organization.name' => 'sample organization'],
            ],
        ];
    }
}
