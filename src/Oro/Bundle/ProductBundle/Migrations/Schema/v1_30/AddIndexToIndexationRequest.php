<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_30;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIndexToIndexationRequest implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_prod_webs_reindex_req_item');

        if (!$table->hasIndex('related_job_id_idx')) {
            $table->addIndex(['related_job_id'], 'related_job_id_idx');
        }
    }
}
