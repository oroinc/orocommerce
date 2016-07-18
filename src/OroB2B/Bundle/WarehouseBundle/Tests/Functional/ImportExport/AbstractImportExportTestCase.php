<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Symfony\Component\Yaml\Yaml;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    /**
     * Return the name of the file that contains data to be tested for import statuses
     *
     * @return string
     */
    abstract public function getImportStatusFile();

    /**
     * Return the name of the file that contains data to be tested for import inventory levels
     *
     * @return string
     */
    abstract public function getImportLevelFile();

    /**
     * @return array
     */
    public function inventoryStatusDataProvider()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $this->getImportStatusFile();

        return Yaml::parse(file_get_contents($filePath));
    }

    /**
     * @return array
     */
    public function inventoryLevelsDataProvider()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $this->getImportLevelFile();

        return Yaml::parse(file_get_contents($filePath));
    }


    /**
     * @param $filePath
     * @return JobResult
     */
    protected function makeImport($filePath)
    {
        $this->cleanUpReader();

        $configuration = [
            'import' => [
                'processorAlias' => 'orob2b_warehouse.warehouse_inventory_level',
                'entityName' => WarehouseInventoryLevel::class,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        return $jobResult;
    }

    /**
     * Return an array of mapping between import header and object and the fields where data should be stored
     *
     * @return array
     */
    protected function getFieldMappings()
    {
        return [
            'SKU' => 'product:sku',
            'Inventory Status' => 'product:inventoryStatus:name',
            'Quantity' => 'quantity',
            'Warehouse' => 'warehouse:name',
            'Unit' => 'productUnitPrecision:unit:code'
        ];
    }

    /**
     * @param array $values
     *
     * @return null|WarehouseInventoryLevel
     */
    protected function getInventoryLevelEntity($values = [])
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Product::class);

        /** @var EntityRepository $productUnitPrecisionRepository */
        $productUnitPrecisionRepository = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(ProductUnitPrecision::class);

        /** @var EntityRepository $warehouseRepository */
        $warehouseRepository = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Warehouse::class);

        /** @var EntityRepository $warehouseInventoryRepository */
        $warehouseInventoryRepository = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(WarehouseInventoryLevel::class);

        $product = $productRepository->findOneBy(['sku' => $values['SKU']]);

        $warehouse = isset($values['Warehouse']) ? $values['Warehouse'] : null;
        if (!$warehouse) {
            $warehouse = $warehouseRepository->getSingularWarehouse();
        } else {
            $warehouse = $warehouseRepository->findOneBy(['name' => $warehouse]);
        }

        $unit = isset($values['Unit']) ? $values['Unit'] : null;
        if (!$unit) {
            $productUnitPrecision = $product->getPrimaryUnitPrecision();
        } else {
            $productUnitPrecision = $productUnitPrecisionRepository->findOneBy(
                [
                    'product' => $product,
                    'unit' => $unit
                ]
            );
        }

        return $warehouseInventoryRepository->findOneBy(
            [
                'product' => $product,
                'warehouse' => $warehouse,
                'productUnitPrecision' => $productUnitPrecision
            ]
        );
    }
    
    /**
     * Retrieves the value for a field of the object, field which is specified in the $fieldMap
     * in the form 'objectField:someField'
     *
     * @param $object
     * @param $fieldMap
     * @return mixed
     */
    protected function getValue($object, $fieldMap)
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
     *
     * @param $entity
     * @param $data
     * @param $fieldsMapping
     * @param array $options
     * @return bool
     */
    protected function assertFields($entity, $data, $fieldsMapping, $options = [])
    {
        foreach ($fieldsMapping as $name => $fieldMap) {
            if (!isset($data[$name])) {
                return false;
            }

            if (empty($data[$name])) {
                return true;
            }

            $value = $data[$name];
            if (isset($options['singularize']) && array_search($name, $options['singularize']) !== false) {
                $value = Inflector::singularize($value);
            }

            $this->assertEquals($value, $this->getValue($entity, $fieldMap));
        }

        return true;
    }

    /**
     * Cleanup reader of errors after each import
     */
    protected function cleanUpReader()
    {
        $reader = $this->getContainer()->get('oro_importexport.reader.csv');
        $reflection = new \ReflectionProperty(get_class($reader), 'file');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
        $reflection = new \ReflectionProperty(get_class($reader), 'header');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
    }
}
