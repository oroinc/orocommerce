<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class LocalizedFallbackValueAwareDataConverterTest extends WebTestCase
{
    private const DEFAULT_EXPECTED_LOCALIZATION = [
        'SKU' => '',
        'Status' => '',
        'Type' => '',
        'Product Family.Code' => '',
        'Inventory Status.Id' => '',
        'Name.default.fallback' => '',
        'Name.default.value' => '',
        'Name.English (United States).fallback' => '',
        'Name.English (United States).value' => '',
        'Name.English (Canada).fallback' => '',
        'Name.English (Canada).value' => '',
        'Name.Spanish.fallback' => '',
        'Name.Spanish.value' => '',
        'Description.default.fallback' => '',
        'Description.default.value' => '',
        'Description.English (United States).fallback' => '',
        'Description.English (United States).value' => '',
        'Description.English (Canada).fallback' => '',
        'Description.English (Canada).value' => '',
        'Description.Spanish.fallback' => '',
        'Description.Spanish.value' => '',
        'Short Description.default.fallback' => '',
        'Short Description.default.value' => '',
        'Short Description.English (United States).fallback' => '',
        'Short Description.English (United States).value' => '',
        'Short Description.English (Canada).fallback' => '',
        'Short Description.English (Canada).value' => '',
        'Short Description.Spanish.fallback' => '',
        'Short Description.Spanish.value' => '',
        'Configurable Attributes' => '',
        'Unit of Quantity.Unit.Code' => '',
        'Unit of Quantity.Precision' => '',
        'Unit of Quantity.Conversion Rate' => '',
        'Unit of Quantity.Sell' => '',
        'Meta title.default.fallback' => '',
        'Meta title.default.value' => '',
        'Meta title.English (United States).fallback' => '',
        'Meta title.English (United States).value' => '',
        'Meta title.Spanish.fallback' => '',
        'Meta title.Spanish.value' => '',
        'Meta title.English (Canada).fallback' => '',
        'Meta title.English (Canada).value' => '',
        'Meta description.default.fallback' => '',
        'Meta description.default.value' => '',
        'Meta description.English (United States).fallback' => '',
        'Meta description.English (United States).value' => '',
        'Meta description.Spanish.fallback' => '',
        'Meta description.Spanish.value' => '',
        'Meta description.English (Canada).fallback' => '',
        'Meta description.English (Canada).value' => '',
        'Meta keywords.default.fallback' => '',
        'Meta keywords.default.value' => '',
        'Meta keywords.English (United States).fallback' => '',
        'Meta keywords.English (United States).value' => '',
        'Meta keywords.Spanish.fallback' => '',
        'Meta keywords.Spanish.value' => '',
        'Meta keywords.English (Canada).fallback' => '',
        'Meta keywords.English (Canada).value' => '',
        'URL Slug.default.fallback' => '',
        'URL Slug.default.value' => '',
        'URL Slug.English (United States).fallback' => '',
        'URL Slug.English (United States).value' => '',
        'URL Slug.Spanish.fallback' => '',
        'URL Slug.Spanish.value' => '',
        'URL Slug.English (Canada).fallback' => '',
        'URL Slug.English (Canada).value' => '',
        'Is Featured' => '',
        'New Arrival' => '',
        'Availability Date' => '',
        'Backorders.value' => '',
        'Decrement Inventory.value' => '',
        'Highlight Low Inventory.value' => '',
        'Inventory Threshold.value' => '',
        'Low Inventory Threshold.value' => '',
        'Managed Inventory.value' => '',
        'Maximum Quantity To Order.value' => '',
        'Minimum Quantity To Order.value' => '',
        'Category.ID' => '',
        'extend.entity.test.wysiwyg' => '',
        'extend.entity.test.wysiwyg_attr' => '',
        'Upcoming.value' => '',
        'Kit Items' => '',
        'Calculate Shipping Based On' => ''
    ];

    private const HEADER_FIELD = 'sku';
    private const HEADER_NOT_SET = 'not_set';
    private $previousHeader;

    private LocalizedFallbackValueAwareDataConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->storeFieldConfigImportExportHeader(self::HEADER_FIELD);
        $this->setFieldConfigImportExportHeader(self::HEADER_FIELD, null);

        $container = $this->getContainer();

        $this->loadFixtures([LoadLocalizationData::class, LoadOrganization::class]);

        $organization = $this->getReference('organization');
        $token = new UsernamePasswordOrganizationToken($this->createMock(AbstractUser::class), 'key', $organization);
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $this->converter = new LocalizedFallbackValueAwareDataConverter(
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator'),
            $container->get('oro_locale.settings')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
        $this->converter->setRegistry($container->get('doctrine'));
        $this->converter->setLocalizedFallbackValueClassName(AbstractLocalizedFallbackValue::class);
        $this->converter->setLocalizationClassName(Localization::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->revertFieldConfigImportExportHeader(self::HEADER_FIELD);
    }

    private function setFieldConfigImportExportHeader(string $fieldName, ?string $newHeader): void
    {
        /** @var ConfigModelManager $manager */
        $manager = $this->getContainer()->get('oro_entity_config.config_model_manager');

        $fieldConfig = $manager->getFieldModel(Product::class, $fieldName);
        $importExportConfig = $fieldConfig->toArray('importexport');

        if (self::HEADER_NOT_SET === $newHeader) {
            unset($importExportConfig['header']);
        } else {
            $importExportConfig['header'] = $newHeader;
        }

        $fieldConfig->fromArray('importexport', $importExportConfig);

        $manager->getEntityManager()->persist($fieldConfig);
        $manager->getEntityManager()->flush();

        $manager->clearCache();
        $this->getContainer()->get('oro_entity_config.config_manager')->clear();
    }

    private function revertFieldConfigImportExportHeader(string $fieldName): void
    {
        $this->setFieldConfigImportExportHeader($fieldName, $this->previousHeader);
    }

    private function storeFieldConfigImportExportHeader(string $fieldName): void
    {
        $fieldConfig = $this->loadFieldConfig($fieldName);

        $importExportConfig = $fieldConfig->toArray('importexport');
        $this->previousHeader = $importExportConfig['header'] ?? self::HEADER_NOT_SET;
    }

    private function loadFieldConfig(string $fieldName): FieldConfigModel
    {
        $manager = $this->getContainer()->get('oro_entity_config.config_model_manager');

        return $manager->getFieldModel(Product::class, $fieldName);
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected)
    {
        $productClass = Product::class;

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    public function importDataProvider(): array
    {
        return [
            'default localization' => [
                ['Name.default.fallback' => 'system', 'Name.default.value' => 'default value'],
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
            ],
            'custom localizations' => [
                [
                    'Name.English (United States).fallback' => 'parent_localization',
                    'Name.English (United States).value' => '',
                    'Name.English (Canada).fallback' => '',
                    'Name.English (Canada).value' => 'English (Canada) value',
                ],
                [
                    'names' => [
                        'English (United States)' => ['fallback' => 'parent_localization'],
                        'English (Canada)' => ['string' => 'English (Canada) value'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected)
    {
        $productClass = Product::class;

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function exportDataProvider(): array
    {
        return [
            'default localization' => [
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
                array_merge(
                    self::DEFAULT_EXPECTED_LOCALIZATION,
                    [
                        'Name.default.fallback' => 'system',
                        'Name.default.value' => 'default value'
                    ]
                ),
            ],

            'custom localization' => [
                [
                    'names' => [
                        'English (United States)' => ['fallback' => 'parent_localization'],
                        'English (Canada)' => ['string' => 'English (Canada) value'],
                    ],
                ],
                array_merge(
                    self::DEFAULT_EXPECTED_LOCALIZATION,
                    [
                        'Name.English (United States).fallback' => 'parent_localization',
                        'Name.English (Canada).value' => 'English (Canada) value'
                    ]
                ),
            ],
        ];
    }
}
