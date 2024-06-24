<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

/**
 * Standart Oro Installer for WebCatalogBundle
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroWebCatalogBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    SlugExtensionAwareInterface,
    ScopeExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;
    use SlugExtensionAwareTrait;
    use ScopeExtensionAwareTrait;
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_4';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroWebCatalogTable($schema);
        $this->createOroContentVariantTable($schema);
        $this->createOroContentNodeTable($schema);
        $this->createOroContentNodeSlugPrototypeTable($schema);
        $this->createOroContentNodeTitleTable($schema);
        $this->createOroContentNodeUrlTable($schema);
        $this->createOroWebCatalogVariantSlugTable($schema);
        $this->createOroWebCatalogNodeScopeTable($schema);
        $this->createOroWebCatalogVariantScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroWebCatalogForeignKeys($schema);
        $this->addOroContentNodeForeignKeys($schema);
        $this->addOroContentNodeTitleForeignKeys($schema);
        $this->addOroContentNodeUrlForeignKeys($schema);
        $this->addOroContentVariantForeignKeys($schema);
        $this->addOroWebCatalogNodeScopeForeignKeys($schema);
        $this->addOroWebCatalogVariantScopeForeignKeys($schema);

        /** Associations generation */
        $this->addContentNodeToSearchTermTable($schema);

        $this->scopeExtension->addScopeAssociation($schema, 'webCatalog', 'oro_web_catalog', 'name');
    }

    /**
     * Create oro_web_catalog table
     */
    private function createOroWebCatalogTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $table->getName());
    }

    /**
     * Create oro_web_catalog_variant table
     */
    private function createOroContentVariantTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('node_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('system_page_route', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('override_variant_configuration', 'boolean', ['default' => false]);
        $table->addColumn('do_not_render_title', 'boolean', ['default' => false]);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['node_id']);
    }

    /**
     * Create oro_web_catalog_content_node table
     */
    private function createOroContentNodeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_content_node');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('web_catalog_id', 'integer');
        $table->addColumn('parent_scope_used', 'boolean', ['default' => true]);
        $table->addColumn('rewrite_variant_title', 'boolean', ['default' => true]);
        $table->addColumn('materialized_path', 'string', ['notnull' => false, 'length' => 1024]);
        $table->addColumn('tree_left', 'integer');
        $table->addColumn('tree_level', 'integer');
        $table->addColumn('tree_right', 'integer');
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $table->getName());
    }

    /**
     * Create oro_web_catalog_node_slug_prot table
     */
    private function createOroContentNodeSlugPrototypeTable(Schema $schema): void
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_web_catalog_node_slug_prot',
            'oro_web_catalog_content_node',
            'node_id'
        );
    }

    /**
     * Create oro_web_catalog_node_title table
     */
    private function createOroContentNodeTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_node_title');
        $table->addColumn('node_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_node_title table
     */
    private function createOroContentNodeUrlTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_node_url');
        $table->addColumn('node_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_variant_slug table
     */
    private function createOroWebCatalogVariantSlugTable(Schema $schema): void
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_web_catalog_variant_slug',
            'oro_web_catalog_variant',
            'content_variant_id'
        );
    }

    /**
     * Create oro_web_catalog_node_scope table
     */
    private function createOroWebCatalogNodeScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_node_scope');
        $table->addColumn('node_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['node_id', 'scope_id']);
    }

    /**
     * Create oro_web_catalog_variant_scope table
     */
    private function createOroWebCatalogVariantScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_variant_scope');
        $table->addColumn('variant_id', 'integer');
        $table->addColumn('scope_id', 'integer');
        $table->setPrimaryKey(['variant_id', 'scope_id']);
    }

    /**
     * Add oro_web_catalog foreign keys.
     */
    private function addOroWebCatalogForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_content_node foreign keys.
     */
    private function addOroContentNodeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_content_node');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog'),
            ['web_catalog_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_web_catalog_node_title foreign keys.
     */
    private function addOroContentNodeTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_node_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['node_id'],
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
     * Add oro_web_catalog_node_title foreign keys.
     */
    private function addOroContentNodeUrlForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_node_url');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['node_id'],
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
     * Add oro_web_catalog_variant foreign keys.
     */
    private function addOroContentVariantForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_variant');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_node_scope foreign keys.
     */
    private function addOroWebCatalogNodeScopeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_node_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['node_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_web_catalog_variant_scope foreign keys.
     */
    private function addOroWebCatalogVariantScopeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_web_catalog_variant_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_variant'),
            ['variant_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addContentNodeToSearchTermTable(Schema $schema): void
    {
        $owningSideTable = $schema->getTable('oro_website_search_search_term');
        $inverseSideTable = $schema->getTable('oro_web_catalog_content_node');

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $owningSideTable,
            'redirectContentNode',
            $inverseSideTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'view' => ['is_displayable' => false],
                'form' => ['is_enabled' => false],
            ]
        );
    }
}
