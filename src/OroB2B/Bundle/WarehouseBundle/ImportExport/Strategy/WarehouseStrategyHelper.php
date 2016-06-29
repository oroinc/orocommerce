<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseStrategyHelper extends AbstractWarehouseInventoryLevelStrategyHelper
{
    /** @var null|integer $warehouseCount  */
    protected $warehouseCount = null;

    /**
     * @inheritdoc
     */
    public function process(
        WarehouseInventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    ) {
        $this->errors = $errors;

        $existingWarehouse = null;
        $importedWarehouse = $importedEntity->getWarehouse();
        if ($this->countWarehouses() > 1) {
            if (!$importedWarehouse && $this->isWarehouseRequired($importData)) {
                $this->addError('orob2b.warehouse.import.error.warehouse_required');

                return null;
            }

            $existingWarehouse = $this->checkAndRetrieveEntity(
                Warehouse::class,
                ['name' => $importedWarehouse->getName()]
            );
        } elseif ($this->countWarehouses() == 1) {
            $existingWarehouse = $this->getSingleWarehouse();
        }

        if (!$existingWarehouse) {
            $this->addError(
                'orob2b.warehouse.import.error.warehouse_inexistent',
                [],
                'orob2b.warehouse.import.error.general_error'
            );

            return null;
        }

        $newEntities['warehouse'] = $existingWarehouse;

        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $importedEntity;
    }

    /**
     * Check if warehouse is required by verifying that at least one Warehouse is found in the
     * system and that there is a Quantity column in the import.
     *
     * @param array $importData
     * @return bool
     */
    protected function isWarehouseRequired(array $importData)
    {
        return $this->countWarehouses() > 1 && array_key_exists('quantity', $importData);
    }

    /**
     * Retrieve the main warehouse from the system
     *
     * @return null|Warehouse
     */
    protected function getSingleWarehouse()
    {
        $manager = $this->databaseHelper->getRegistry()->getManagerForClass(Warehouse::class);
        $repository = $manager->getRepository(Warehouse::class);

        return $repository->getSingularWarehouse();
    }

    /**
     * Return the count of warehouses in the system. Because it will be called multiple times
     * during a process step, once the result is returned from repository it is stored
     * in a variable so that on next call we won't make another request to repository level.
     *
     * @return int|null
     */
    protected function countWarehouses()
    {
        if ($this->warehouseCount !== null) {
            return $this->warehouseCount;
        }

        $manager = $this->databaseHelper->getRegistry()->getManagerForClass(Warehouse::class);
        $repository = $manager->getRepository(Warehouse::class);

        return $this->warehouseCount = $repository->countWarehouses();
    }
}
