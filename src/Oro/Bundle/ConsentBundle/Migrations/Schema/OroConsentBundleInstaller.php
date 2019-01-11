<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/** Bundle install migrations */
class OroConsentBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    const CONSENT_TABLE_NAME = 'oro_consent';
    const CONSENT_NAME_TABLE_NAME = 'oro_consent_name';
    const CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME = 'oro_consent_acceptance';

    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
        $this->addConsentAcceptanceCustomerUserRelation($schema);

        $table = $schema->getTable(self::CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME);
        $table->addUniqueIndex(['consent_id','customerUser_id'], 'oro_customeru_consent_uidx');
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
        $table->addColumn('landing_page_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
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
            $schema->getTable('oro_cms_page'),
            ['landing_page_id'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => null]
        );
    }

    /**
     * Adds CustomerUser.acceptedConsents and ConsentAcceptance.customerUser Many-To-One bidirectional relation
     * @param Schema $schema
     */
    private function addConsentAcceptanceCustomerUserRelation(Schema $schema): void
    {
        $inverseSideTable = $schema->getTable('oro_customer_user');
        $owningSideTable = $schema->getTable(self::CONSENT_CUSTOMER_ACCEPTANCE_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $owningSideTable,
            'customerUser',
            $inverseSideTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE',
                ],
                'datagrid' => ['is_visible' => false],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $owningSideTable,
            'customerUser',
            $inverseSideTable,
            'acceptedConsents',
            ['id'],
            ['id'],
            ['id'],
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'cascade' => ['persist'],
                    'on_delete' => 'CASCADE',
                    'orphanRemoval' => true,
                    'fetch' => ClassMetadataInfo::FETCH_LAZY
                ],
                'datagrid' => ['is_visible' => false],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );
    }
}
