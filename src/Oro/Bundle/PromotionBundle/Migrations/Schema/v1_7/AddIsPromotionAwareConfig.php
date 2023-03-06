<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

class AddIsPromotionAwareConfig implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                Order::class,
                'promotion',
                'is_promotion_aware',
                true
            )
        );
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                Order::class,
                'promotion',
                'is_coupon_aware',
                true
            )
        );
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                Checkout::class,
                'promotion',
                'is_coupon_aware',
                true
            )
        );
    }
}
