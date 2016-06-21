<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Integration;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy\WarehouseInventoryLevelStrategy;

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
        $this->importProcessor->setSerializer($this->getContainer()->get('oro_importexport.serializer'));
        $this->importProcessor->setDataConverter($this->getContainer()->get('orob2b_warehouse.importexport.data_converter.warehouse_inventory_level'));

        $strategy = new WarehouseInventoryLevelStrategy(
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_importexport.strategy.import.helper'),
            $this->getContainer()->get('oro_importexport.field.field_helper'),
            $this->getContainer()->get('oro_importexport.field.database_helper'),
            $this->getContainer()->get('oro_entity.entity_class_name_provider'),
            $this->getContainer()->get('translator')
        );

        $this->importProcessor->setStrategy($strategy);
        $this->importProcessor->setEntityName(WarehouseInventoryLevel::class);
    }

    /**
     * @param $expectedClass
     * @param $item
     *
     * @dataProvider processDataProvider
     */
    public function testProcess($fieldsMapping, $testData)
    {
        $context = new Context([]);
        $this->importProcessor->setImportExportContext($context);

        foreach ($testData as $dataSet) {
            $context->setValue('itemData', $dataSet['data']);
            $entity = $this->importProcessor->process($dataSet['data']);

            $this->assertInstanceOf($dataSet['class'], $entity);
            $this->assertTrue($this->assertFields($entity, $dataSet['data'], $fieldsMapping, isset($dataSet['options']) ? $dataSet['options'] : []));
        }
    }

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

    protected function getValue($object, $fieldMap)
    {
        $objectFields = explode(':', $fieldMap);

        foreach ($objectFields as $objectField) {
            $getterMethod = 'get' . ucfirst($objectField);
            $object = $object->{$getterMethod}();
        }

        return $object;
    }

    /**
     * return array
     */
    abstract public function processDataProvider();
}
