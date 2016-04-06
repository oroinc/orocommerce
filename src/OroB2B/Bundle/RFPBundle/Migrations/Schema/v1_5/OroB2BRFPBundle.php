<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_5;

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
        /** Table Modification */
        $this->modifyRfpRequestTable($schema);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function modifyRfpRequestTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->changeColumn('phone', ['notnull' => false]);
        $table->changeColumn('note', ['notnull' => false]);
    }
}
