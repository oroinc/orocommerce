<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIndexesForRequestDates implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_rfp_request');
        if (!$table->hasIndex('idx_oro_rfq_request_created_at')) {
            $table->addIndex(['created_at'], 'idx_oro_rfq_request_created_at');
        }
        if (!$table->hasIndex('idx_oro_rfq_request_updated_at')) {
            $table->addIndex(['updated_at'], 'idx_oro_rfq_request_updated_at');
        }
    }
}
