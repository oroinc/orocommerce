<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * MySQL implementation of temp table manipulator
 */
class MySqlTempTableManipulator extends AbstractTempTableManipulator
{
    public function createTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()
            ->executeQuery(sprintf(
                'CREATE TEMPORARY TABLE %s LIKE %s',
                $this->getTempTableNameForEntity($className, $identifier),
                $this->helper->getTableName($className)
            ));
    }
}
