<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_4;

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
        $table->addColumn('deleted_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('cancellation_reason', 'text', ['notnull' => false]);
    }
}
