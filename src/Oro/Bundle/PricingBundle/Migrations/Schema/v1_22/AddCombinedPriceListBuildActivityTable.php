<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add `oro_price_list_combined_build_activity' table
 */
class AddCombinedPriceListBuildActivityTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_price_list_combined_build_activity')) {
            $this->createOroPriceListCombinedBuildActivityTable($schema);
            $this->addOroPriceListCombinedBuildActivityForeignKeys($schema);
        }
    }

    protected function createOroPriceListCombinedBuildActivityTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_combined_build_activity');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_job_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['parent_job_id'], 'oro_cpl_build_activity_job_idx');
        $table->setPrimaryKey(['id']);
    }

    protected function addOroPriceListCombinedBuildActivityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_combined_build_activity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
