<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroConsentBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createConsentTable($schema);
        $this->createOroConsentNameTable($schema);
        $this->createOroConsentAcceptanceTable($schema);

        /** Foreign keys generation **/
        $this->addOroConsentForeignKeys($schema);
        $this->addOroConsentNameForeignKeys($schema);
        $this->addOroConsentAcceptanceForeignKeys($schema);

        $this->addConsentAcceptanceCustomerUserRelation($schema);
    }

    private function createConsentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_consent');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('content_node_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('mandatory', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('declined_notification', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_consent_created_at');
        $table->addIndex(['content_node_id'], 'oro_consent_content_node_id');
    }

    private function createOroConsentNameTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_consent_name');
        $table->addColumn('consent_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['consent_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'oro_consent_name_trans_localized_value_id');
    }

    private function createOroConsentAcceptanceTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_consent_acceptance');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('consent_id', 'integer');
        $table->addColumn('landing_page_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    private function addOroConsentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_consent');
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

    private function addOroConsentNameForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_consent_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_consent'),
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

    private function addOroConsentAcceptanceForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_consent_acceptance');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_consent'),
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
     */
    private function addConsentAcceptanceCustomerUserRelation(Schema $schema): void
    {
        $consentAcceptanceTable = $schema->getTable('oro_consent_acceptance');
        $customerUserTable = $schema->getTable('oro_customer_user');

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $consentAcceptanceTable,
            'customerUser',
            $customerUserTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );
        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $consentAcceptanceTable,
            'customerUser',
            $customerUserTable,
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
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );

        $consentAcceptanceTable->addUniqueIndex(['consent_id','customerUser_id'], 'oro_customeru_consent_uidx');
    }
}
