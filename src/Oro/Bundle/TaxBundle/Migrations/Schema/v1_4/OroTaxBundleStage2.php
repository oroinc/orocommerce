<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTaxBundleStage2 implements Migration, OrderedMigrationInterface
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->restoreForeigneys($schema);
    }

    private function restoreForeigneys(Schema $schema)
    {
        $table = $schema->getTable('oro_tax_cus_grp_tc_cus_grp');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tax_customer_tax_code'),
            ['customer_group_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_tax_cus_tax_code_cus');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tax_customer_tax_code'),
            ['customer_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addUniqueIndex(['customer_id'], 'UNIQ_53167F2A9B6B5FBA');

        $table = $schema->getTable('oro_tax_cus_grp_tc_cus_grp');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tax_customer_tax_code'),
            ['customer_group_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_tax_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_tax_customer_tax_code'),
            ['customer_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
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
