<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroWebsiteSearchBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWebsiteSearchIndexDecimalTable($schema);
        $this->createOroWebsiteSearchIndexIntegerTable($schema);
        $this->createOroWebsiteSearchIndexDatetimeTable($schema);
        $this->createOroWebsiteSearchItemTable($schema);
        $this->createOroWebsiteSearchIndexTextTable($schema);

        /** Foreign keys generation **/
        $this->addOroWebsiteSearchIndexDecimalForeignKeys($schema);
        $this->addOroWebsiteSearchIndexIntegerForeignKeys($schema);
        $this->addOroWebsiteSearchIndexDatetimeForeignKeys($schema);
        $this->addOroWebsiteSearchIndexTextForeignKeys($schema);
    }

    /**
     * Create oro_website_search_decimal table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchIndexDecimalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_decimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'decimal', ['scale' => 2]);
        $table->addIndex(['item_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_integer table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchIndexIntegerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_integer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'integer', []);
        $table->addIndex(['item_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_datetime table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchIndexDatetimeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_datetime');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['item_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_item table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('changed', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['entity', 'record_id'], 'oro_website_search_item_uidx');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity'], 'oro_website_search_item_idxe', []);
        $table->addIndex(['alias'], 'oro_website_search_item_idxa', []);
    }

    /**
     * Create oro_website_search_text table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchIndexTextTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_text');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'text', []);
        $table->addIndex(['item_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_website_search_decimal foreign keys.
     * @param Schema $schema
     */
    protected function addOroWebsiteSearchIndexDecimalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_decimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_website_search_integer foreign keys.
     * @param Schema $schema
     */
    protected function addOroWebsiteSearchIndexIntegerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_integer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_website_search_datetime foreign keys.
     * @param Schema $schema
     */
    protected function addOroWebsiteSearchIndexDatetimeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_datetime');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_website_search_text foreign keys.
     * @param Schema $schema
     */
    protected function addOroWebsiteSearchIndexTextForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_text');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }
}
