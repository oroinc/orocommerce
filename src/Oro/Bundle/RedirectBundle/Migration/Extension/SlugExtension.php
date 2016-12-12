<?php

namespace Oro\Bundle\RedirectBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

class SlugExtension
{
    /**
     * @param Schema $schema
     * @param string $relationTableName
     * @param string $referencingTable
     * @param string $joinColumnName
     */
    public function addLocalizedSlugPrototypes(Schema $schema, $relationTableName, $referencingTable, $joinColumnName)
    {
        $table = $schema->createTable($relationTableName);
        $table->addColumn($joinColumnName, 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey([$joinColumnName, 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable($referencingTable),
            [$joinColumnName],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @param string $relationTableName
     * @param string $referencingTable
     * @param string $joinColumnName
     */
    public function addSlugs(Schema $schema, $relationTableName, $referencingTable, $joinColumnName)
    {
        $table = $schema->createTable($relationTableName);
        $table->addColumn($joinColumnName, 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey([$joinColumnName, 'slug_id']);
        $table->addUniqueIndex(['slug_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable($referencingTable),
            [$joinColumnName],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
