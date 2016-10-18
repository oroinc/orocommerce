<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRFPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->updateOroRfpRequestTable($schema);

        /** Foreign keys generation **/
        $this->addOroRfpRequestForeignKeys($schema);
    }

    /**
     * Update oro_rfp_request table
     *
     * @param Schema $schema
     */
    protected function updateOroRfpRequestTable(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
    }

    /**
     * Add oro_rfp_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRfpRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            [
                'onUpdate' => null,
                'onDelete' => 'SET NULL'
            ]
        );
    }
}
