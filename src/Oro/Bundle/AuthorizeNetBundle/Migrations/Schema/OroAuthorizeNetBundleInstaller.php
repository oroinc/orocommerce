<?php

namespace Oro\Bundle\AuthorizeNetBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroAuthorizeNetBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
        $this->createOroAuthorizeNetCreditCardLblTable($schema);
        $this->createOroAuthorizeNetCreditCardShLblTable($schema);
        $this->addOroAuthorizeNetCreditCardLblForeignKeys($schema);
        $this->addOroAuthorizeNetCreditCardShLblForeignKeys($schema);
    }

    /**
     * Update oro_integration_transport table
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('au_net_api_login', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('au_net_transaction_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('au_net_client_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('au_net_credit_card_action', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('au_net_allowed_card_types', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('au_net_test_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('au_net_require_cvv_entry', 'boolean', ['default' => '1', 'notnull' => false]);
    }

    /**
     * Create oro_au_net_credit_card_lbl table
     * @param Schema $schema
     */
    protected function createOroAuthorizeNetCreditCardLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_au_net_credit_card_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_au_net_credit_card_sh_lbl table
     * @param Schema $schema
     */
    protected function createOroAuthorizeNetCreditCardShLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_au_net_credit_card_sh_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_au_net_credit_card_lbl foreign keys.
     * @param Schema $schema
     */
    protected function addOroAuthorizeNetCreditCardLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_au_net_credit_card_lbl');
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
     * Add oro_au_net_credit_card_sh_lbl foreign keys.
     * @param Schema $schema
     */
    protected function addOroAuthorizeNetCreditCardShLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_au_net_credit_card_sh_lbl');
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
}
