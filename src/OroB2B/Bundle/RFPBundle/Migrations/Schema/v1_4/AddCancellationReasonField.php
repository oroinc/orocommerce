<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCancellationReasonField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addColumn('cancellation_reason', 'text', ['notnull' => false]);
    }
}
