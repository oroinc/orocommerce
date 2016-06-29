<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Strategy;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

interface WarehouseInventoryLevelStrategyHelperInterface
{
    /**
     * Process imported entity, validate and extract existing entity base on the imported one,
     * make updates or create new objects.
     *
     * @param WarehouseInventoryLevel $importedEntity
     * @param array $importData
     * @param array $newEntities
     * @param array $errors
     * @return mixed
     */
    public function process(
        WarehouseInventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    );

    /**
     * @param WarehouseInventoryLevelStrategyHelperInterface $successor
     */
    public function setSuccessor(WarehouseInventoryLevelStrategyHelperInterface $successor);

    /**
     * Return the errors added by the validation process of the current strategy helper or all of
     * its successor if $deep parameter is true
     *
     * @param bool $deep
     * @return array
     */
    public function getErrors($deep = false);
}
