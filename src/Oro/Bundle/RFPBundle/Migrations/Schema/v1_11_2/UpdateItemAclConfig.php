<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_11_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Set group_name and category security options for RequestProduct and RequestProductItem
 */
class UpdateItemAclConfig implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                RequestProduct::class,
                'security',
                'group_name',
                'commerce'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                RequestProduct::class,
                'security',
                'category',
                'quotes'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                RequestProductItem::class,
                'security',
                'group_name',
                'commerce'
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                RequestProductItem::class,
                'security',
                'category',
                'quotes'
            )
        );
    }
}
