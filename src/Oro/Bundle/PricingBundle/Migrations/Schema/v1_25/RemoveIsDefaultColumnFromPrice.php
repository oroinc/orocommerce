<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_25;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes default column from price list entity.
 */
class RemoveIsDefaultColumnFromPrice implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_price_list');
        if (!$table->hasColumn('is_default')) {
            return;
        }

        $table->dropColumn('is_default');
        $queries->addPostQuery(new RemoveFieldQuery('Oro\Bundle\PricingBundle\Entity\PriceList', 'default'));
    }
}
