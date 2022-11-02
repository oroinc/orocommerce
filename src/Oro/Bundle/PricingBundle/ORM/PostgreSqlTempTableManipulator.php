<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * PostgreSQL implementation of temp table manipulator
 */
class PostgreSqlTempTableManipulator extends AbstractTempTableManipulator
{
    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function truncateTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()->executeQuery(sprintf(
            'TRUNCATE %s',
            $this->getTempTableNameForEntity($className, $identifier)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function dropTempTableForEntity(string $className, $identifier)
    {
        $this->registry->getConnection()->executeQuery(sprintf(
            'DROP TABLE IF EXISTS %s',
            $this->getTempTableNameForEntity($className, $identifier)
        ));
    }
}
