<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * MySQL implementation of temp table manipulator
 */
class MySqlTempTableManipulator extends AbstractTempTableManipulator
{
    /**
     * {@inheritdoc}
     */
    public function createTempTableForEntity(string $className, $identifier)
    {
        // Accordingly to https://dev.mysql.com/doc/refman/8.0/en/create-temporary-table.html
        // CREATE TEMPORARY TABLE %s SELECT * FROM %s LIMIT 0 must be used instead of CREATE TEMPORARY TABLE ... LIKE
        $this->registry->getConnection()
            ->executeQuery(sprintf(
                'CREATE TEMPORARY TABLE IF NOT EXISTS %s SELECT * FROM %s LIMIT 0',
                $this->getTempTableNameForEntity($className, $identifier),
                $this->helper->getTableName($className)
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()->executeQuery(sprintf(
            'DELETE FROM %s',
            $this->getTempTableNameForEntity($className, $identifier)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function dropTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()->executeQuery(sprintf(
            'DROP TEMPORARY TABLE IF EXISTS %s',
            $this->getTempTableNameForEntity($className, $identifier)
        ));
    }
}
