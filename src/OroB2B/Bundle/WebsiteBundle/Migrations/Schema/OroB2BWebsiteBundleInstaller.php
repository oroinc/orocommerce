<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BWebsiteBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    const WEBSITE_TABLE_NAME = 'orob2b_website';

    /** @var NoteExtension */
    protected $noteExtension;

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
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BRelatedWebsiteTable($schema);
        $this->createOrob2BWebsiteTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BRelatedWebsiteForeignKeys($schema);
        $this->addOrob2BWebsiteForeignKeys($schema);
        $this->addNoteAssociations($schema);
    }

    /**
     * Create orob2b_related_website table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRelatedWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_related_website');
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('related_website_id', 'integer', []);
        $table->setPrimaryKey(['website_id', 'related_website_id']);
    }

    /**
     * Create orob2b_website table
     *
     * @param Schema $schema
     */
    protected function createOrob2BWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable(self::WEBSITE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('url', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['name']);
        $table->addUniqueIndex(['url']);
        $table->addIndex(['created_at'], 'idx_orob2b_website_created_at', []);
        $table->addIndex(['updated_at'], 'idx_orob2b_website_updated_at', []);
    }

    /**
     * Add orob2b_related_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRelatedWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_related_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['related_website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BWebsiteForeignKeys(Schema $schema)
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
}
