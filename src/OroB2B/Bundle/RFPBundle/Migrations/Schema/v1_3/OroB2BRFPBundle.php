<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BRFPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->changeOrob2BRfpRequestTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BRfpRequestForeignKeys($schema);
    }

    /**
     * Create orob2b_rfp_request table
     *
     * @param Schema $schema
     */
    protected function changeOrob2BRfpRequestTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
    }

    /**
     * Add orob2b_rfp_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
