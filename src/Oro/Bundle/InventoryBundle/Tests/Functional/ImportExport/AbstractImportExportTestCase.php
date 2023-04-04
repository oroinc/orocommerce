<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\ImportExport;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    /**
     * Return the name of the file that contains data to be tested for import statuses
     */
    abstract public function getImportStatusFile(): string;

    /**
     * Return the name of the file that contains data to be tested for import inventory levels
     */
    abstract public function getImportLevelFile(): string;

    public function inventoryStatusDataProvider(): array
    {
        $filePath = $this->getFilePath() . $this->getImportStatusFile();

        return Yaml::parse(file_get_contents($filePath));
    }

    public function inventoryLevelsDataProvider(): array
    {
        $filePath = $this->getFilePath() . $this->getImportLevelFile();

        return Yaml::parse(file_get_contents($filePath));
    }

    /**
     * Return bundle relative path where test data is found
     */
    protected function getFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    }

    protected function makeImport(string $filePath): JobResult
    {
        $this->cleanUpReader();

        $configuration = [
            'import' => [
                'processorAlias' => 'oro_inventory.inventory_level',
                'entityName' => InventoryLevel::class,
                'filePath' => $filePath,
            ],
        ];

        return $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );
    }

    /**
     * Return an array of mapping between import header and object and the fields where data should be stored
     */
    protected function getFieldMappings(): array
    {
        return [
            'SKU' => 'product:sku',
            'Inventory Status' => 'product:inventoryStatus:name',
            'Quantity' => 'quantity',
            'Unit' => 'productUnitPrecision:unit:code'
        ];
    }

    protected function getInventoryLevelEntity(array $values = []): ?InventoryLevel
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $product = $doctrine->getRepository(Product::class)->findOneBy(['sku' => $values['SKU']]);

        $unit = $values['Unit'] ?? null;
        if ($unit) {
            $productUnitPrecision = $doctrine->getRepository(ProductUnitPrecision::class)->findOneBy([
                'product' => $product,
                'unit' => $unit
            ]);
        } else {
            $productUnitPrecision = $product->getPrimaryUnitPrecision();
        }

        return $doctrine->getRepository(InventoryLevel::class)->findOneBy([
            'product' => $product,
            'productUnitPrecision' => $productUnitPrecision
        ]);
    }

    /**
     * Retrieves the value for a field of the object, field which is specified in the $fieldMap
     * in the form 'objectField:someField'
     */
    protected function getValue(object $object, string $fieldMap): mixed
    {
        $objectFields = explode(':', $fieldMap);

        foreach ($objectFields as $objectField) {
            $getterMethod = 'get' . ucfirst($objectField);
            $object = $object->$getterMethod();
        }

        return $object;
    }

    /**
     * Verify if the entity contains the expected values for the fields mentioned in the
     * $fieldsMapping list.
     */
    protected function assertFields(object $entity, array $data, array $fieldsMapping, array $options = []): bool
    {
        foreach ($fieldsMapping as $name => $fieldMap) {
            if (!isset($data[$name])) {
                return false;
            }

            if (empty($data[$name])) {
                return true;
            }

            $value = $data[$name];
            if (isset($options['singularize']) && in_array($name, $options['singularize'], true)) {
                $value = (new InflectorFactory())->build()->singularize($value);
            }

            $this->assertEquals($value, $this->getValue($entity, $fieldMap));
        }

        return true;
    }

    /**
     * Cleanup reader of errors after each import
     */
    protected function cleanUpReader(): void
    {
        $reader = $this->getContainer()->get('oro_importexport.reader.csv');
        ReflectionUtil::setPropertyValue($reader, 'file', null);
        ReflectionUtil::setPropertyValue($reader, 'header', null);
    }
}
