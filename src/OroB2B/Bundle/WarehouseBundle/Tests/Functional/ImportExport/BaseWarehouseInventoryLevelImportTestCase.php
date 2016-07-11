<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\ImportExport;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\InventoryStatusesStrategyHelper;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\ProductUnitStrategyHelper;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategy;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategyHelper;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategyHelperInterface;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseStrategyHelper;

/**
 * @dbIsolation
 */
abstract class BaseWarehouseInventoryLevelImportTestCase extends WebTestCase
{
    /** @var ImportProcessor $importProcessor */
    protected $importProcessor;

    protected function setUp()
    {
        $this->initClient();
        $this->importProcessor = new ImportProcessor();
        $this->importProcessor->setSerializer(
            $this->getContainer()->get('orob2b_warehouse.importexport.serializer.warehouse_inventory_level')
        );
        $this->importProcessor->setDataConverter(
            $this->getContainer()->get('orob2b_warehouse.importexport.inventory_level_converter')
        );

        $strategy = new WarehouseInventoryLevelStrategy(
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_importexport.strategy.import.helper'),
            $this->getContainer()->get('oro_importexport.field.field_helper'),
            $this->getContainer()->get('oro_importexport.field.database_helper'),
            $this->getContainer()->get('oro_entity.entity_class_name_provider'),
            $this->getContainer()->get('translator'),
            $this->getContainer()->get('oro_importexport.strategy.new_entities_helper')
        );

        $warehouseInventoryLevelHelper = $this->createStrategyHelper(WarehouseInventoryLevelStrategyHelper::class);
        $productUnitHelper = $this->createStrategyHelper(
            ProductUnitStrategyHelper::class,
            $warehouseInventoryLevelHelper,
            true
        );

        $warehouseHelper = $this->createStrategyHelper(WarehouseStrategyHelper::class, $productUnitHelper);
        $inventoryStatusHelper = $this->createStrategyHelper(InventoryStatusesStrategyHelper::class, $warehouseHelper);

        $strategy->setInventoryLevelStrategyHelper($inventoryStatusHelper);

        $this->importProcessor->setStrategy($strategy);
        $this->importProcessor->setEntityName(WarehouseInventoryLevel::class);
    }

    protected function createStrategyHelper($class, $successor = null, $transformer = false)
    {
        if ($transformer) {
            /** @var WarehouseInventoryLevelStrategyHelperInterface $helper */
            $helper = new $class(
                $this->getContainer()->get('oro_importexport.field.database_helper'),
                $this->getContainer()->get('translator'),
                $this->getContainer()->get('orob2b_warehouse.transformer.inventory_product_unit')
            );
        } else {
            /** @var WarehouseInventoryLevelStrategyHelperInterface $helper */
            $helper = new $class(
                $this->getContainer()->get('oro_importexport.field.database_helper'),
                $this->getContainer()->get('translator')
            );
        }

        if ($successor) {
            $helper->setSuccessor($successor);
        }

        return $helper;
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
     * return array
     */
    abstract public function processDataProvider();
}
