<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroPayPalBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
        $this->createOroPaypalAllowedCardTpsTable($schema);
        $this->createOroPaypalCrdtCrdPmntActnTable($schema);
        $this->createOroPaypalCreditCardLblTable($schema);
        $this->createOroPaypalCreditCardShLblTable($schema);
        $this->createOroPaypalCreditCardTypeTable($schema);
        $this->createOroPaypalXprdChktPmntActnTable($schema);
        $this->createOroPaypalXprssChktLblTable($schema);
        $this->createOroPaypalXprssChktShrtLblTable($schema);
        $this->addOroIntegrationTransportForeignKeys($schema);
        $this->addOroPaypalAllowedCardTpsForeignKeys($schema);
        $this->addOroPaypalCreditCardLblForeignKeys($schema);
        $this->addOroPaypalCreditCardShLblForeignKeys($schema);
        $this->addOroPaypalXprssChktLblForeignKeys($schema);
        $this->addOroPaypalXprssChktShrtLblForeignKeys($schema);
    }

    /**
     * Update oro_integration_transport table
     *
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('pp_express_checkout_action_id', 'integer', ['notnull' => false]);
        $table->addColumn('pp_credit_card_action_id', 'integer', ['notnull' => false]);
        $table->addColumn('pp_express_checkout_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_partner', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_vendor', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_test_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_debug_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_require_cvv_entry', 'boolean', ['default' => '1', 'notnull' => false]);
        $table->addColumn('pp_zero_amount_authorization', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_auth_for_req_amount', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_use_proxy', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_proxy_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_proxy_port', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_enable_ssl_verification', 'boolean', ['default' => '1', 'notnull' => false]);
    }

    /**
     * Create oro_paypal_allowed_card_tps table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalAllowedCardTpsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_allowed_card_tps');
        $table->addColumn('pp_settings_id', 'integer', []);
        $table->addColumn('credit_card_type_id', 'integer', []);
        $table->setPrimaryKey(['pp_settings_id', 'credit_card_type_id']);
    }

    /**
     * Create oro_paypal_crdt_crd_pmnt_actn table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCrdtCrdPmntActnTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_crdt_crd_pmnt_actn');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string', ['notnull' => true, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_paypal_credit_card_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCreditCardLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_credit_card_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_92E5B87EEB576E89');
    }

    /**
     * Create oro_paypal_credit_card_sh_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCreditCardShLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_credit_card_sh_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_55FE472FEB576E89');
    }

    /**
     * Create oro_paypal_credit_card_types table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCreditCardTypeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_credit_card_types');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string', ['notnull' => true, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_paypal_xprs_chkt_pmnt_actn table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalXprdChktPmntActnTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_xprs_chkt_pmnt_actn');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string', ['notnull' => true, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_paypal_xprss_chkt_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalXprssChktLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_386D1FC6EB576E89');
    }

    /**
     * Create oro_paypal_xprss_chkt_shrt_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalXprssChktShrtLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_A9419ECEB576E89');
    }

    /**
     * Add oro_paypal_allowed_card_tps foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalAllowedCardTpsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_allowed_card_tps');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_paypal_credit_card_types'),
            ['credit_card_type_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['pp_settings_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_credit_card_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalCreditCardLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_credit_card_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_credit_card_sh_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalCreditCardShLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_credit_card_sh_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalXprssChktLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_shrt_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalXprssChktShrtLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_integration_transport foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroIntegrationTransportForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_paypal_xprs_chkt_pmnt_actn'),
            ['pp_express_checkout_action_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_paypal_crdt_crd_pmnt_actn'),
            ['pp_credit_card_action_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
