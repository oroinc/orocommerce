<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * PostgreSQL implementation of temp table manipulator
 */
class PostgreSqlTempTableManipulator extends AbstractTempTableManipulator
{
    /**
     * {@inheritDoc}
     */
    public function createTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()
            ->executeQuery(sprintf(
                'CREATE TEMP TABLE IF NOT EXISTS %s AS TABLE %s WITH NO DATA',
                $this->getTempTableNameForEntity($className, $identifier),
                $this->helper->getTableName($className)
            ));
    }
}
