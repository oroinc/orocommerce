<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundleStage2 implements
    Migration,
    OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->restoreIndexes($schema);
    }

    private function restoreIndexes(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_to_cus_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_price_list_cus_gr_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addUniqueIndex(['customer_group_id', 'website_id'], 'oro_price_list_cus_gr_fb_unq');

        $table = $schema->getTable('oro_cmb_plist_to_cus_gr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addUniqueIndex(['customer_group_id', 'website_id'], 'oro_cpl_to_cus_gr_ws_unq');

        $schema->getTable('oro_price_list_to_customer')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer'),
                ['customer_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );

        $schema->getTable('oro_price_list_cus_fb')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer'),
                ['customer_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );

        $schema->getTable('oro_cmb_price_list_to_cus')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer'),
                ['customer_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        $schema->getTable('oro_price_list_cus_fb')
            ->addUniqueIndex(['customer_id', 'website_id'], 'oro_price_list_cus_fb_unq');

        $schema->getTable('oro_cmb_price_list_to_cus')
            ->addUniqueIndex(
                ['customer_id', 'website_id'],
                'oro_cpl_to_cus_ws_unq'
            );
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 2;
    }
}
