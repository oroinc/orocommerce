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
        'ID' => '',
        'Title.default.fallback' => '',
        'Title.default.value' => '',
        'Title.English (United States).fallback' => '',
        'Title.English (United States).value' => '',
        'Parent category.ID' => '',
        'Parent category.Title' => '',
        'Short Description.default.fallback' => '',
        'Short Description.default.value' => '',
        'Short Description.English (United States).fallback' => '',
        'Short Description.English (United States).value' => '',
        'Long Description.default.fallback' => '',
        'Long Description.default.value' => '',
        'Long Description.English (United States).fallback' => '',
        'Long Description.English (United States).value' => '',
        'Meta keywords.default.fallback' => '',
        'Meta keywords.default.value' => '',
        'Meta keywords.English (United States).fallback' => '',
        'Meta keywords.English (United States).value' => '',
        'Meta title.default.fallback' => '',
        'Meta title.default.value' => '',
        'Meta title.English (United States).fallback' => '',
        'Meta title.English (United States).value' => '',
        'URL Slug.default.fallback' => '',
        'URL Slug.default.value' => '',
        'URL Slug.English (United States).fallback' => '',
        'URL Slug.English (United States).value' => '',
        'Meta description.default.fallback' => '',
        'Meta description.default.value' => '',
        'Meta description.English (United States).fallback' => '',
        'Meta description.English (United States).value' => '',
    ];

    /** @var CategoryDataConverter */
    private $converter;

    #[\Override]
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
                array_merge(self::ALL_FIELDS, ['Parent category.Title' => 'sample title']),
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
