<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_26;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateProductWebsiteReindexRequestItem implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroProductWebsiteReindeRequestItem($schema);
    }

    /**
     * Create oro_prod_webs_reindex_req_item table
     */
    protected function createOroProductWebsiteReindeRequestItem(Schema $schema)
    {
        if ($schema->hasTable('oro_prod_webs_reindex_req_item')) {
            return;
        }

        $table = $schema->createTable('oro_prod_webs_reindex_req_item');
        $table->addColumn('related_job_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addIndex(
            ['related_job_id', 'website_id'],
            'idx_oro_prod_webs_reindex_req_item_main_ids'
        );
    }
}
