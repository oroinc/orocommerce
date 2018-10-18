<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

class UpdateOrderFieldsFormTypes implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'totalValue',
                'form',
                'form_type',
                OroMoneyType::class,
                'oro_money'
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'subtotalValue',
                'form',
                'form_type',
                OroMoneyType::class,
                'oro_money'
            )
        );
    }
}
