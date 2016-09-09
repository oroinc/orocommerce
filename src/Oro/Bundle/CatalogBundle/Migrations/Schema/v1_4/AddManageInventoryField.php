<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddManageInventoryField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageInventoryFieldToCategory($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToCategory(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addColumn('manage_inventory_fallback_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['manage_inventory_fallback_id'], 'UNIQ_FBD712DDA4E4A513');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_fallback_value'),
            ['manage_inventory_fallback_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'CASCADE']
        );
    }
}
