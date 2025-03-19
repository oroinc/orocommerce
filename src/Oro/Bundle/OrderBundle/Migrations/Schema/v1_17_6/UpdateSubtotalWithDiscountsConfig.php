<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_17_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

class UpdateSubtotalWithDiscountsConfig implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'subtotalWithDiscounts',
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'subtotalWithDiscounts',
                'multicurrency',
                'target',
                'subtotalDiscounts'
            )
        );
    }
}
