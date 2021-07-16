<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddContentBlockTable implements
    Migration,
    ExtendExtensionAwareInterface,
    OrderedMigrationInterface
{
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
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsContentBlockTable($schema);
        $this->createOroCmsContentBlockTitleTable($schema);
        $this->createOroCmsContentBlockScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsContentBlockTitleForeignKeys($schema);
        $this->addOroCmsContentBlockScopeForeignKeys($schema);
        $this->addOrganizationForeignKeys($schema);
    }

    /**
     * Create `oro_cms_content_block` table
     */
    public function createOroCmsContentBlockTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('alias', 'string', ['notnull' => true, 'length' => 100]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['alias']);
    }

    /**
     * Create `oro_cms_content_block_title` table
     */
    protected function createOroCmsContentBlockTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block_title');
        $table->addColumn('content_block_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['content_block_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create `oro_cms_content_block_scope` table
     */
    protected function createOroCmsContentBlockScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_content_block_scope');
        $table->addColumn('content_block_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['content_block_id', 'scope_id']);
    }

    /**
     * Add `oro_cms_content_block_title` foreign keys.
     */
    protected function addOroCmsContentBlockTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
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
     * Add `oro_cms_content_block_scope` foreign keys.
     */
    protected function addOroCmsContentBlockScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_content_block foreign keys.
     */
    protected function addOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_content_block');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
