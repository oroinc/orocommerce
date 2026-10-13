<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductMultiEnumValues;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class ProductMultiEnumAttributeImportExportTest extends WebTestCase
{
    private const FIELD_NAME = 'multienum_field';
    private const LABEL_WITH_METACHARACTERS = 'Horsepower (HP) [max]';
    private const HEADER_NOT_SET = 'not_set';

    private ?string $previousHeader;
    private bool $previousExcluded;
    private ProductDataConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadProductMultiEnumValues::class]);

        $importExportConfig = $this->loadFieldConfigModel()->toArray('importexport');
        $this->previousHeader = $importExportConfig['header'] ?? self::HEADER_NOT_SET;
        $this->previousExcluded = $importExportConfig['excluded'] ?? false;

        $this->applyFieldImportExportConfig(self::LABEL_WITH_METACHARACTERS, false);

        $this->converter = self::getContainer()->get('oro_product.importexport.data_converter.product');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->applyFieldImportExportConfig(
            self::HEADER_NOT_SET === $this->previousHeader ? null : $this->previousHeader,
            $this->previousExcluded
        );
    }

    public function testExportProducesLiteralHeaderForEachOption(): void
    {
        $exported = $this->converter->convertToExportFormat([
            self::FIELD_NAME => [
                ['name' => 'First Value'],
                ['name' => 'Second Value'],
                ['name' => 'Third Value'],
                ['name' => 'Fourth Value'],
            ],
        ]);

        self::assertArrayHasKey(self::LABEL_WITH_METACHARACTERS . '.1.Name', $exported);
        self::assertSame('First Value', $exported[self::LABEL_WITH_METACHARACTERS . '.1.Name']);

        self::assertArrayHasKey(self::LABEL_WITH_METACHARACTERS . '.2.Name', $exported);
        self::assertSame('Second Value', $exported[self::LABEL_WITH_METACHARACTERS . '.2.Name']);

        self::assertArrayHasKey(self::LABEL_WITH_METACHARACTERS . '.3.Name', $exported);
        self::assertSame('Third Value', $exported[self::LABEL_WITH_METACHARACTERS . '.3.Name']);

        self::assertArrayHasKey(self::LABEL_WITH_METACHARACTERS . '.4.Name', $exported);
        self::assertSame('Fourth Value', $exported[self::LABEL_WITH_METACHARACTERS . '.4.Name']);
    }

    public function testDataTemplateColumnsIncludeTheCustomAttribute(): void
    {
        /** @var ProductDataConverter $templateConverter */
        $templateConverter = self::getContainer()->get('oro_product.importexport.data_converter.product_template');

        $exported = $templateConverter->convertToExportFormat([self::FIELD_NAME => []]);

        // Guards ProductDataConverter::isAvailableAttribute(): on Community edition the attribute-scope
        // config never registers 'is_global', so the field must stay available rather than being dropped.
        self::assertArrayHasKey(self::LABEL_WITH_METACHARACTERS . '.1.Name', $exported);
    }

    public function testImportReadsBackTheExportedHeaders(): void
    {
        $imported = $this->converter->convertToImportFormat([
            self::LABEL_WITH_METACHARACTERS . '.1.Name' => 'First Value',
            self::LABEL_WITH_METACHARACTERS . '.2.Name' => 'Second Value',
            self::LABEL_WITH_METACHARACTERS . '.3.Name' => 'Third Value',
            self::LABEL_WITH_METACHARACTERS . '.4.Name' => 'Fourth Value',
        ]);

        self::assertArrayHasKey(self::FIELD_NAME, $imported);
        self::assertSame(
            [
                ['name' => 'First Value'],
                ['name' => 'Second Value'],
                ['name' => 'Third Value'],
                ['name' => 'Fourth Value'],
            ],
            $imported[self::FIELD_NAME]
        );
    }

    private function applyFieldImportExportConfig(?string $header, bool $excluded): void
    {
        $manager = $this->getConfigModelManager();
        $fieldConfig = $this->loadFieldConfigModel();
        $importExportConfig = $fieldConfig->toArray('importexport');

        if (null === $header) {
            unset($importExportConfig['header']);
        } else {
            $importExportConfig['header'] = $header;
        }
        $importExportConfig['excluded'] = $excluded;

        $fieldConfig->fromArray('importexport', $importExportConfig);

        $entityManager = $manager->getEntityManager();
        $entityManager->persist($fieldConfig);
        $entityManager->flush();

        $manager->clearCache();
        self::getContainer()->get('oro_entity_config.config_manager')->clear();
    }

    private function loadFieldConfigModel(): FieldConfigModel
    {
        return $this->getConfigModelManager()->getFieldModel(Product::class, self::FIELD_NAME);
    }

    private function getConfigModelManager(): ConfigModelManager
    {
        return self::getContainer()->get('oro_entity_config.config_model_manager');
    }
}
