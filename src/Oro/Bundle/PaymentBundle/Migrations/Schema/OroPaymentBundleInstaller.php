<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_6';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPaymentTransactionTable($schema);
        $this->createOroPaymentStatusTable($schema);
        $this->createOroPaymentMethodConfigTable($schema);
        $this->createOroPaymentMethodsConfigsRuleTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationPostalCodeTable($schema);

        $this->addOroPaymentTransactionForeignKeys($schema);
        $this->addOroPaymentMethodConfigForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationPostalCodeForeignKeys($schema);
    }

    /**
     * Create table for PaymentTransaction entity
     *
     * @param Schema $schema
     */
    protected function createOroPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_transaction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('access_identifier', 'string', ['length' => 255]);
        $table->addColumn('access_token', 'string', ['length' => 255]);
        $table->addColumn('payment_method', 'string', ['length' => 255]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('reference', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('amount', 'string', ['length' => 255]);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('active', 'boolean', []);
        $table->addColumn('successful', 'boolean', []);
        $table->addColumn('source_payment_transaction', 'integer', ['notnull' => false]);
        $table->addColumn('request', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('response', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('transaction_options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'oro_pay_trans_access_uidx');
        $table->addIndex(['source_payment_transaction']);
    }

    /**
     * Create oro_payment_method_config table
     *
     * @param Schema $schema
     */
    protected function createOroPaymentMethodConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_method_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('configs_rule_id', 'integer', []);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['configs_rule_id'], 'idx_oro_payment_method_config_configs_rule_id', []);
    }

    /**
     * Create oro_payment_mtds_cfgs_rl table
     *
     * @param Schema $schema
     */
    protected function createOroPaymentMethodsConfigsRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_mtds_cfgs_rl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('currency', 'string', ['notnull' => true, 'length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'idx_oro_payment_mtds_cfgs_rl_rule_id', []);
    }

    /**
     * Create oro_payment_mtds_cfgs_rl_d table
     *
     * @param Schema $schema
     */
    protected function createOroPaymentMethodsConfigsRuleDestinationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_mtds_cfgs_rl_d');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('configs_rule_id', 'integer', []);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['configs_rule_id'], 'idx_oro_payment_mtds_cfgs_rl_d_configs_rule_id', []);
        $table->addIndex(['region_code'], 'idx_oro_payment_mtds_cfgs_rl_d_region_code', []);
        $table->addIndex(['country_code'], 'idx_oro_payment_mtds_cfgs_rl_d_country_code', []);
    }

    /**
     * Create oro_payment_mtdscfgsrl_dst_pc table
     *
     * @param Schema $schema
     */
    protected function createOroPaymentMethodsConfigsRuleDestinationPostalCodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_mtdscfgsrl_dst_pc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('destination_id', 'integer', []);
        $table->addColumn('name', 'text', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['destination_id'], 'idx_oro_payment_mtdscfgsrl_dst_pc_destination_id', []);
    }

    /**
     * Add oro_payment_transaction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaymentTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_transaction'),
            ['source_payment_transaction'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['frontend_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_payment_method_config foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaymentMethodConfigForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPaymentMethodsConfigsRuleForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPaymentMethodsConfigsRuleDestinationForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPaymentMethodsConfigsRuleDestinationPostalCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_mtdscfgsrl_dst_pc');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_mtds_cfgs_rl_d'),
            ['destination_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_payment_status
     *
     * @param Schema $schema
     */
    protected function createOroPaymentStatusTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('payment_status', 'string', ['length' => 255]);
        $table->addUniqueIndex(['entity_class', 'entity_identifier'], 'oro_payment_status_unique');
        $table->setPrimaryKey(['id']);
    }
}
