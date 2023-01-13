<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Create `oro_combined_price_gc' table.
 *
 * This table stores CPL removal request by GC. Records are removed from this table only when actual CPL removal is
 * performed. Presence of CPL in the table does not mean that it will be actually removed, the request actuality is
 * checked and the moment of actual removal.
 */
class AddCombinedPriceListGCTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_price_list_combined_gc')) {
            return;
        }

        $table = $schema->createTable('oro_price_list_combined_gc');
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('requested_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['combined_price_list_id'], 'oro_cpl_gc_unq_idx');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
