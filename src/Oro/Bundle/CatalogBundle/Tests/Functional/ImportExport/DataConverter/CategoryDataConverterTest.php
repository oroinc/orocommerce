<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\DataConverter\CategoryDataConverter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryDataConverterTest extends WebTestCase
{
    public const ALL_FIELDS = [
        'titles.default.fallback' => '',
        'titles.default.value' => '',
        'id' => '',
        'titles.English.fallback' => '',
        'titles.English.value' => '',
        'parentCategory.id' => '',
        'parentCategory.title' => '',
        'shortDescriptions.default.fallback' => '',
        'shortDescriptions.default.value' => '',
        'shortDescriptions.English.fallback' => '',
        'shortDescriptions.English.value' => '',
        'longDescriptions.default.fallback' => '',
        'longDescriptions.default.value' => '',
        'longDescriptions.English.fallback' => '',
        'longDescriptions.English.value' => '',
        'metaDescriptions.default.fallback' => '',
        'metaDescriptions.default.value' => '',
        'metaDescriptions.English.fallback' => '',
        'metaDescriptions.English.value' => '',
        'metaKeywords.default.fallback' => '',
        'metaKeywords.default.value' => '',
        'metaKeywords.English.fallback' => '',
        'metaKeywords.English.value' => '',
        'metaTitles.default.fallback' => '',
        'metaTitles.default.value' => '',
        'metaTitles.English.fallback' => '',
        'metaTitles.English.value' => '',
        'slugPrototypes.default.fallback' => '',
        'slugPrototypes.default.value' => '',
        'slugPrototypes.English.fallback' => '',
        'slugPrototypes.English.value' => '',
    ];

    /** @var CategoryDataConverter */
    private $converter;

    /** {@inheritdoc} */
    protected function setUp()
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
        $this->converter->setLocalizedFallbackValueClassName(LocalizedFallbackValue::class);
        $this->converter->setLocalizationClassName(Localization::class);
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected): void
    {
        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    /**
     * @return array
     */
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
     * @param array $data
     * @param array $expected
     *
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected): void
    {
        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
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
