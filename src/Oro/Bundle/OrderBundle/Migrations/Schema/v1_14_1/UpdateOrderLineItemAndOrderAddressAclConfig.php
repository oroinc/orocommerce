<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Set group_name and category security options for OrderLineItem and OrderAddress.
 */
class UpdateOrderLineItemAndOrderAddressAclConfig implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                OrderLineItem::class,
                'security',
                'group_name',
                'commerce'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                OrderLineItem::class,
                'security',
                'category',
                'orders'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                OrderAddress::class,
                'security',
                'group_name',
                'commerce'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                OrderAddress::class,
                'security',
                'category',
                'orders'
            )
        );
    }
}
