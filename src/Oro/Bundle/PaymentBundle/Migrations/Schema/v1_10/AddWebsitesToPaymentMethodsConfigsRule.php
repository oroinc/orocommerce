<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddWebsitesToPaymentMethodsConfigsRule implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPaymentMtdsRuleWebsiteTable($schema);
        $this->addOroPaymentMtdsRuleWebsiteForeignKeys($schema);
    }

    /**
     * Create oro_payment_mtds_rule_website table
     */
    protected function createOroPaymentMtdsRuleWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_mtds_rule_website');
        $table->addColumn('oro_payment_mtds_cfgs_rl_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['oro_payment_mtds_cfgs_rl_id', 'website_id']);
        $table->addIndex(['oro_payment_mtds_cfgs_rl_id'], 'IDX_8316A7FAAE67BF3C', []);
    }

    /**
     * Add oro_payment_mtds_rule_website foreign keys.
     */
    protected function addOroPaymentMtdsRuleWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_mtds_rule_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_mtds_cfgs_rl'),
            ['oro_payment_mtds_cfgs_rl_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
