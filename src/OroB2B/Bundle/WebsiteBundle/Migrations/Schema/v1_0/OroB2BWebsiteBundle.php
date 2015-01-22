<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BWebsiteBundle implements Migration
{
    const TABLE_NAME = 'orob2b_website';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BLocaleTable($schema);
        $this->createOrob2BRelatedWebsiteTable($schema);
        $this->createOrob2BWebsiteTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BLocaleForeignKeys($schema);
        $this->addOrob2BRelatedWebsiteForeignKeys($schema);
        $this->addOrob2BWebsiteForeignKeys($schema);
    }

    /**
     * Create orob2b_locale table
     *
     * @param Schema $schema
     */
    protected function createOrob2BLocaleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_locale');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 10]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_4F6E51C677153098');
        $table->addIndex(['parent_id'], 'IDX_4F6E51C6727ACA70', []);
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
        $table->addIndex(['website_id'], 'IDX_8E360C7418F45C82', []);
        $table->addIndex(['related_website_id'], 'IDX_8E360C7492395432', []);
    }

    /**
     * Create orob2b_website table
     *
     * @param Schema $schema
     */
    protected function createOrob2BWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_website');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_CBB2CF835E237E06');
        $table->addUniqueIndex(['url'], 'UNIQ_CBB2CF83F47645AE');
        $table->addIndex(['business_unit_owner_id'], 'IDX_CBB2CF8359294170', []);
        $table->addIndex(['organization_id'], 'IDX_CBB2CF8332C8A3DE', []);
    }

    /**
     * Add orob2b_locale foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BLocaleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_locale');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['parent_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
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
        $table = $schema->getTable('orob2b_website');
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
}
