<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundleStage2 implements Migration, OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOroOrderAddressForeignKeys($schema);
        $this->updateOroOrderListLineItemTable($schema);
        $this->addOroOrderLineItemForeignKeys($schema);
    }

    private function addOroOrderAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_address');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_address'),
            ['customer_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_address'),
            ['customer_user_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function updateOroOrderListLineItemTable(Schema $schema)
    {
        $table = $schema->getTable('oro_order_line_item');
        $table->addColumn('parent_product_id', 'integer', ['notnull' => false]);
    }

    private function addOroOrderLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['parent_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 2;
    }
}
