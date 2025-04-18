<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Set disablePromotions default value.
 */
class SetDefaultValueToDisablePromotions implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order');
        $table->getColumn('disablePromotions')->setDefault(false);
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'disablePromotions',
                'extend',
                'default',
                false
            )
        );
        $queries->addQuery(new SqlMigrationQuery(
            'UPDATE oro_order SET disablePromotions = false WHERE disablePromotions IS NULL'
        ));
    }
}
