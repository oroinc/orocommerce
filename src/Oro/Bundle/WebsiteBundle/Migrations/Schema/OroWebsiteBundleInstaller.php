<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migrations\Schema\OroScopeBundleInstaller;

class OroWebsiteBundleInstaller implements Installation, NoteExtensionAwareInterface, ExtendExtensionAwareInterface
{
    const WEBSITE_TABLE_NAME = 'oro_website';

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheritDoc
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRelatedWebsiteTable($schema);
        $this->createOroWebsiteTable($schema);

        /** Foreign keys generation **/
        $this->addOroRelatedWebsiteForeignKeys($schema);
        $this->addOroWebsiteForeignKeys($schema);
        $this->addNoteAssociations($schema);

        $this->addRelationsToScope($schema);
    }

    /**
     * Create oro_related_website table
     *
     * @param Schema $schema
     */
    protected function createOroRelatedWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_related_website');
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('related_website_id', 'integer', []);
        $table->setPrimaryKey(['website_id', 'related_website_id']);
    }

    /**
     * Create oro_website table
     *
     * @param Schema $schema
     */
    protected function createOroWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable(self::WEBSITE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('is_default', 'boolean', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['name']);
        $table->addIndex(['created_at'], 'idx_oro_website_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_website_updated_at', []);
    }

    /**
     * Add oro_related_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRelatedWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_related_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['related_website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::WEBSITE_TABLE_NAME);
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
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, self::WEBSITE_TABLE_NAME);
    }

    /**
     * @param Schema $schema
     */
    private function addRelationsToScope(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'website',
            OroWebsiteBundleInstaller::WEBSITE_TABLE_NAME,
            'id',
            [],
            RelationType::MANY_TO_ONE,
            ['onDelete' => 'CASCADE']
        );
    }
}
