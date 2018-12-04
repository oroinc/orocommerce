<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/** Bundle install migrations */
class OroConsentBundleInstaller implements Installation
{
    const CONSENT_TABLE_NAME = 'oro_consent';
    const CONSENT_NAME_TABLE_NAME = 'oro_consent_name';
    const CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME = 'oro_consent_acceptance';

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createConsentTable($schema);
        $this->createOroConsentNameTable($schema);
        $this->createOroConsentAcceptanceTable($schema);

        $this->addOroConsentForeignKeys($schema);
        $this->addOroConsentNameForeignKeys($schema);
        $this->addOroConsentAcceptanceForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createConsentTable(Schema $schema)
    {
        $table = $schema->createTable(self::CONSENT_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('content_node_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('mandatory', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('declined_notification', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_consent_created_at', []);
        $table->addIndex(['content_node_id'], 'oro_consent_content_node_id');
    }

    /**
     * @param Schema $schema
     */
    private function createOroConsentNameTable(Schema $schema)
    {
        $table = $schema->createTable(self::CONSENT_NAME_TABLE_NAME);
        $table->addColumn('consent_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['consent_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'oro_consent_name_trans_localized_value_id');
    }

    /**
     * @param Schema $schema
     */
    private function createOroConsentAcceptanceTable(Schema $schema)
    {
        $table = $schema->createTable(self::CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('consent_id', 'integer', []);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('landing_page_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['consent_id','customer_user_id'], 'oro_customer_consent_uidx');
    }

    /**
     * @param Schema $schema
     */
    private function addOroConsentForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::CONSENT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['content_node_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    private function addOroConsentNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::CONSENT_NAME_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::CONSENT_TABLE_NAME),
            ['consent_id'],
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
     * @param Schema $schema
     */
    private function addOroConsentAcceptanceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::CONSENT_TABLE_NAME),
            ['consent_id'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['landing_page_id'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => null]
        );
    }
}
