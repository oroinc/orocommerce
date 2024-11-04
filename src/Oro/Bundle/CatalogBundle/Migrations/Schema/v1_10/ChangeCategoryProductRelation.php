<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeCategoryProductRelation implements
    Migration,
    OrderedMigrationInterface,
    ConnectionAwareInterface,
    ExtendExtensionAwareInterface,
    DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;
    use ConnectionAwareTrait;
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 100;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addCategoryProductRelation($schema);

        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $queries->addQuery(new UpdateCategoryIdsInProductsPqSql());
        } elseif ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $queries->addQuery(new UpdateCategoryIdsInProductsMySql());
        }

        $this->removeOldCategoryProductRelation($queries);
    }

    private function addCategoryProductRelation(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $targetTable = $schema->getTable('oro_product');

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

    private function removeOldCategoryProductRelation(QueryBag $queries): void
    {
        $queries->addQuery('DROP TABLE IF EXISTS oro_category_to_product');
    }
}
