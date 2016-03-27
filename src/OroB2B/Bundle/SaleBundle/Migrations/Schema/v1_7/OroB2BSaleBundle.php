<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables update **/
        $this->alterAddressTable($schema);
        $this->createOroB2BSaleSelectedOfferTable($schema);
        $this->addOroB2BSaleSelectedOfferForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterAddressTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_address');
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create orob2b_sale_selected_offer table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleSelectedOfferTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_selected_offer');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('quote_product_offer', 'integer', []);
        $table->addColumn('quantity', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'quote_product_offer']);
    }

    /**
     * Add orob2b_sale_selected_offer foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleSelectedOfferForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_selected_offer');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_prod_offer'),
            ['quote_product_offer'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
