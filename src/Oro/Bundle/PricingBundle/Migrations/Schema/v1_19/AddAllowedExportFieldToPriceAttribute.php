<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add new column to price attribute table.
 */
class AddAllowedExportFieldToPriceAttribute implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_price_attribute_pl');
        if (!$table->hasColumn('is_enabled_in_export')) {
            $table->addColumn('is_enabled_in_export', 'boolean', ['notnull' => true, 'default' => false]);
        }
    }
}
