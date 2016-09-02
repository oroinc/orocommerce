<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_5;

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
        $this->addManageInventoryField($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryField($schema)
    {
        $table = $schema->getTable('orob2b_product');
        $table->addColumn('manage_inventory_fallback_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['manage_inventory_fallback_id'], 'UNIQ_5F9796C9A4E4A513');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_field_fallback_val'),
            ['manage_inventory_fallback_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
