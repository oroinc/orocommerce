<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_2;

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
        /** Tables changes **/
        $this->changeOroB2BSaleQuoteTable($schema);

        /** Foreign keys changes **/
        $this->addOroB2BSaleQuoteForeignKeys($schema);
    }

    /**
     * Change orob2b_sale_quote table
     *
     * @param Schema $schema
     */
    protected function changeOroB2BSaleQuoteTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
    }

    /**
     * Add orob2b_sale_quote foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_customer'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
