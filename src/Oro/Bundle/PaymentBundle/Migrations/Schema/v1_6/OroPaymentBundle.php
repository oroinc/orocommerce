<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroPaymentMethodConfigTable($schema);
        $this->createOroPaymentMethodsConfigsRuleTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationPostalCodeTable($schema);

        $this->addOroPaymentMethodConfigForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationPostalCodeForeignKeys($schema);
    }

    /**
     * Create oro_payment_method_config table
     */
    private function createOroPaymentMethodConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_method_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('configs_rule_id', 'integer');
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['configs_rule_id'], 'idx_oro_payment_method_config_configs_rule_id');
    }

    /**
     * Create oro_payment_mtds_cfgs_rl table
     */
    private function createOroPaymentMethodsConfigsRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_mtds_cfgs_rl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('currency', 'string', ['notnull' => true, 'length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'idx_oro_payment_mtds_cfgs_rl_rule_id');
    }

    /**
     * Create oro_payment_mtds_cfgs_rl_d table
     */
    private function createOroPaymentMethodsConfigsRuleDestinationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_mtds_cfgs_rl_d');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('configs_rule_id', 'integer');
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['configs_rule_id'], 'idx_oro_payment_mtds_cfgs_rl_d_configs_rule_id');
        $table->addIndex(['region_code'], 'idx_oro_payment_mtds_cfgs_rl_d_region_code');
        $table->addIndex(['country_code'], 'idx_oro_payment_mtds_cfgs_rl_d_country_code');
    }

    /**
     * Create oro_payment_mtdscfgsrl_dst_pc table
     */
    private function createOroPaymentMethodsConfigsRuleDestinationPostalCodeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_mtdscfgsrl_dst_pc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('destination_id', 'integer');
        $table->addColumn('name', 'text');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['destination_id'], 'idx_oro_payment_mtdscfgsrl_dst_pc_destination_id');
    }

    /**
     * Add oro_payment_method_config foreign keys.
     */
    private function addOroPaymentMethodConfigForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_payment_method_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_mtds_cfgs_rl'),
            ['configs_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_payment_mtds_cfgs_rl foreign keys.
     */
    private function addOroPaymentMethodsConfigsRuleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_payment_mtds_cfgs_rl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_payment_mtds_cfgs_rl_d foreign keys.
     */
    private function addOroPaymentMethodsConfigsRuleDestinationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_payment_mtds_cfgs_rl_d');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_mtds_cfgs_rl'),
            ['configs_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_payment_mtdscfgsrl_dst_pc foreign keys.
     */
    private function addOroPaymentMethodsConfigsRuleDestinationPostalCodeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_payment_mtdscfgsrl_dst_pc');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_mtds_cfgs_rl_d'),
            ['destination_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
