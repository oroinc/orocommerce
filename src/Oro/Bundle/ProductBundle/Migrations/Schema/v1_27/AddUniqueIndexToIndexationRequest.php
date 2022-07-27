<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_27;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUniqueIndexToIndexationRequest implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_prod_webs_reindex_req_item');

        if ($table->hasIndex('idx_oro_prod_webs_reindex_req_item_main_ids')) {
            $table->dropIndex('idx_oro_prod_webs_reindex_req_item_main_ids');
        }

        if (!$table->hasIndex('prod_webs_reindex_req_uniq_idx')) {
            $queries->addPreQuery(new RemoveDuplicateIndexationRequestsQuery());
            $table->addUniqueIndex(['product_id', 'related_job_id', 'website_id'], 'prod_webs_reindex_req_uniq_idx');
        }
    }
}
