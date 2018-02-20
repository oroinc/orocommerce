<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;

class ChangeCategoryProductRelation implements
    Migration,
    OrderedMigrationInterface,
    ConnectionAwareInterface,
    ExtendExtensionAwareInterface,
    DatabasePlatformAwareInterface
{
    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param AbstractPlatform $platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCategoryProductRelation($schema);

        if ($this->connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $updateQuery = new UpdateCategoryIdsInProductsPqSql();
            $updateQuery->setExtendExtension($this->extendExtension);
            $queries->addQuery($updateQuery);
        } elseif ($this->connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_MYSQL) {
            $updateQuery = new UpdateCategoryIdsInProductsMySql();
            $updateQuery->setExtendExtension($this->extendExtension);
            $queries->addQuery($updateQuery);
        }

        $this->removeOldCategoryProductRelation($queries);
    }

    /**
     * @param Schema $schema
     */
    protected function addCategoryProductRelation(Schema $schema)
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);
        $targetTable = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'cascade' => ['persist'],
                    'on_delete' => 'SET NULL',
                ]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'products',
            ['name'],
            ['name'],
            ['name'],
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ]
            ]
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function removeOldCategoryProductRelation(QueryBag $queries)
    {
        $queries->addQuery('DROP TABLE IF EXISTS oro_category_to_product');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 100;
    }
}
