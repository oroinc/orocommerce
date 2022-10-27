<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class RemoveProductPriceAcl implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $entityClass = 'Oro\\Bundle\\PricingBundle\\Entity\\ProductPrice';

        $query = new ParametrizedSqlMigrationQuery();
        $query->addSql(
            'DELETE FROM acl_classes WHERE class_type = :class_type',
            ['class_type' => $entityClass],
            ['class_type' => Types::STRING]
        );

        $queries->addPostQuery($query);
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(ProductPrice::class, 'security', 'type', null)
        );
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(ProductPrice::class, 'security', 'group_name', null)
        );
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(ProductPrice::class, 'security', 'category', null)
        );
    }
}
