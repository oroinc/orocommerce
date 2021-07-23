<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_4_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateExtendRelationDataQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

/**
 * Add cascade remove rule for the applied promotions to avoid data inconsistency with doctrine and database.
 */
class UpdateAppliedPromotionCascadeConfigToOrder implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $fullRelationName = implode(
            '|',
            [RelationType::MANY_TO_ONE, AppliedPromotion::class, Order::class, 'order']
        );
        $queries->addQuery(
            new UpdateExtendRelationDataQuery(
                Order::class,
                $fullRelationName,
                'cascade',
                ['remove']
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'appliedPromotions',
                'extend',
                'cascade',
                ['remove']
            )
        );
    }
}
