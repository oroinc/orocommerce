<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEnabledToCoupon implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroPromotionCouponTable($schema);
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_promotion_coupon SET enabled = :enabled',
                ['enabled' => true],
                ['enabled' => Types::BOOLEAN]
            )
        );
    }

    protected function updateOroPromotionCouponTable(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_coupon');
        $table->addColumn('enabled', 'boolean', ['default' => false]);
    }
}
