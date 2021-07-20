<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_10;

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
        $this->updateOroRfpRequestTable($schema);
        $this->addOroRfpRequestForeignKeys($schema);
    }

    /**
     * Add website relation to oro_rfp_request table.
     */
    protected function updateOroRfpRequestTable(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addIndex(['website_id'], 'idx_de1d53c18f45c82', []);
    }

    protected function addOroRfpRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
