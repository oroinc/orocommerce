<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_3;

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
