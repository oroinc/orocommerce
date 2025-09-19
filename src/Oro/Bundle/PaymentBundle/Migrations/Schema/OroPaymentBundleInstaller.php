<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroPaymentBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v7_0_0_1';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroPaymentTransactionTable($schema);
        $this->createOroPaymentStatusTable($schema);
        $this->createOroPaymentMethodConfigTable($schema);
        $this->createOroPaymentMethodsConfigsRuleTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationTable($schema);
        $this->createOroPaymentMethodsConfigsRuleDestinationPostalCodeTable($schema);
        $this->createOroPaymentMtdsRuleWebsiteTable($schema);

        $this->addOroPaymentTransactionForeignKeys($schema);
        $this->addOroPaymentMethodConfigForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationForeignKeys($schema);
        $this->addOroPaymentMethodsConfigsRuleDestinationPostalCodeForeignKeys($schema);
        $this->addOroPaymentMtdsRuleWebsiteForeignKeys($schema);
    }

    /**
     * Create table for PaymentTransaction entity
     */
    private function createOroPaymentTransactionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_transaction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer');
        $table->addColumn('access_identifier', 'string', ['length' => 255]);
        $table->addColumn('access_token', 'string', ['length' => 255]);
        $table->addColumn('payment_method', 'string', ['length' => 255]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('reference', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('amount', 'string', ['length' => 255]);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('active', 'boolean');
        $table->addColumn('successful', 'boolean');
        $table->addColumn('source_payment_transaction', 'integer', ['notnull' => false]);
        $table->addColumn('request', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('response', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('webhook_request_logs', 'secure_array', [
            'notnull' => false,
            'comment' => '(DC2Type:secure_array)',
        ]);
        $table->addColumn('transaction_options', 'secure_array', [
            'notnull' => false,
            'comment' => '(DC2Type:secure_array)'
        ]);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'oro_pay_trans_access_uidx');
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
    }

    /**
     * Create oro_payment_mtds_cfgs_rl table
     */
    private function createOroPaymentMethodsConfigsRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_mtds_cfgs_rl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer');
        $table->addColumn('currency', 'string', ['notnull' => true, 'length' => 3]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_payment_mtds_cfgs_rl');
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
    }

    /**
     * Add oro_payment_transaction foreign keys.
     */
    private function addOroPaymentTransactionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_transaction'),
            ['source_payment_transaction'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
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

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
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

    /**
     * Create oro_payment_status
     */
    private function createOroPaymentStatusTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer');
        $table->addColumn('payment_status', 'string', ['length' => 255]);
        $table->addColumn('forced', 'boolean', ['default' => false]);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity_class', 'entity_identifier'], 'oro_payment_status_unique');
    }

    /**
     * Create oro_payment_mtds_rule_website table
     */
    private function createOroPaymentMtdsRuleWebsiteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_payment_mtds_rule_website');
        $table->addColumn('oro_payment_mtds_cfgs_rl_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->setPrimaryKey(['oro_payment_mtds_cfgs_rl_id', 'website_id']);
        $table->addIndex(['oro_payment_mtds_cfgs_rl_id'], 'IDX_8316A7FAAE67BF3C');
    }

    /**
     * Add oro_payment_mtds_rule_website foreign keys.
     */
    private function addOroPaymentMtdsRuleWebsiteForeignKeys(Schema $schema): void
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
