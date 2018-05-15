<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateOrderLineItemTableMigration implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order_line_item');
        if (!$table->hasColumn('product_name')) {
            $table->addColumn('product_name', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('product_variant_fields')) {
            $table->addColumn('product_variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        }

        $keyName = 'fk_32715f0b2c7e20a';
        if (!$table->hasForeignKey($keyName)) {
            return;
        }

        $foreignKey = $table->getForeignKey($keyName);
        if ($foreignKey->getOption('onDelete') === 'CASCADE') {
            $table->removeForeignKey($keyName);
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_product'),
                ['parent_product_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );
        }
    }
}
