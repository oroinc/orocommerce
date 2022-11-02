<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
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
    ScopeExtensionAwareInterface
{
    use ScopeExtensionAwareTrait;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
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

        $this->scopeExtension->addScopeAssociation(
            $schema,
            'webCatalog',
            'oro_web_catalog',
            'name'
        );
    }

    /**
     * Create oro_web_catalog table
     */
    protected function createOroWebCatalogTable(Schema $schema)
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
    protected function createOroContentVariantTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('node_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('system_page_route', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('override_variant_configuration', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['node_id']);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
    }

    /**
     * Create oro_web_catalog_content_node table
     */
    protected function createOroContentNodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_content_node');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('web_catalog_id', 'integer', []);
        $table->addColumn('parent_scope_used', 'boolean', ['default' => true]);
        $table->addColumn('rewrite_variant_title', 'boolean', ['default' => true]);
        $table->addColumn('materialized_path', 'string', ['notnull' => false, 'length' => 1024]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $table->getName());
    }

    /**
     * Create oro_web_catalog_node_slug_prot table
     */
    protected function createOroContentNodeSlugPrototypeTable(Schema $schema)
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
    protected function createOroContentNodeTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_title');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_node_title table
     */
    protected function createOroContentNodeUrlTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_url');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_variant_slug table
     */
    protected function createOroWebCatalogVariantSlugTable(Schema $schema)
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
    protected function createOroWebCatalogNodeScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_scope');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'scope_id']);
    }

    /**
     * Create oro_web_catalog_variant_scope table
     */
    protected function createOroWebCatalogVariantScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_variant_scope');
        $table->addColumn('variant_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['variant_id', 'scope_id']);
    }

    /**
     * Add oro_web_catalog foreign keys.
     */
    protected function addOroWebCatalogForeignKeys(Schema $schema)
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
    protected function addOroContentNodeForeignKeys(Schema $schema)
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
    protected function addOroContentNodeTitleForeignKeys(Schema $schema)
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
    protected function addOroContentNodeUrlForeignKeys(Schema $schema)
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
    protected function addOroContentVariantForeignKeys(Schema $schema)
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
    protected function addOroWebCatalogNodeScopeForeignKeys(Schema $schema)
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
    protected function addOroWebCatalogVariantScopeForeignKeys(Schema $schema)
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
}
