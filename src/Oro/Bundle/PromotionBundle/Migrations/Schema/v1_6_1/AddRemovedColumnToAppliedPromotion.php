<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds removed field to applied promotions to check is applied promotion removed from backend
 */
class AddRemovedColumnToAppliedPromotion implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_promotion_applied')) {
            $table = $schema->getTable('oro_promotion_applied');
            if (!$table->hasColumn('removed')) {
                $table->addColumn('removed', 'boolean', ['default' => '0']);
            }
        }
    }
}
