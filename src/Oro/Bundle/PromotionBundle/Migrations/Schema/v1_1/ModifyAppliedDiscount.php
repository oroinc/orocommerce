<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ModifyAppliedDiscount implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyAppliedDiscount($schema);
    }

    /**
     * Add fields to applied discount.
     *
     * @param Schema $schema
     */
    protected function modifyAppliedDiscount(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('coupon_code', 'string', ['notnull' => false, 'length' => 255]);
    }
}
