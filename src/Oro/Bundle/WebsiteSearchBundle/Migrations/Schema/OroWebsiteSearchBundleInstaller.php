<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Migration\MysqlVersionCheckTrait;
use Oro\Bundle\SearchBundle\Migration\UseMyIsamEngineQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroWebsiteSearchBundleInstaller implements Installation, ContainerAwareInterface, DatabasePlatformAwareInterface
{
    use ContainerAwareTrait;
    use DatabasePlatformAwareTrait;
    use MysqlVersionCheckTrait;

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
        $this->createOroWebsiteSearchDecimalTable($schema);
        $this->createOroWebsiteSearchIntegerTable($schema);
        $this->createOroWebsiteSearchDatetimeTable($schema);
        $this->createOroWebsiteSearchItemTable($schema);
        $this->createOroWebsiteSearchTextTable($schema, $queries);

        /** Foreign keys generation **/
        $this->addOroWebsiteSearchDecimalForeignKeys($schema);
        $this->addOroWebsiteSearchIntegerForeignKeys($schema);
        $this->addOroWebsiteSearchDatetimeForeignKeys($schema);
        $this->addOroWebsiteSearchTextForeignKeys($schema);
    }

    /**
     * Create oro_website_search_decimal table
     */
    protected function createOroWebsiteSearchDecimalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_decimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'decimal', ['precision' => 21, 'scale' => 6]);
        $table->addIndex(['item_id']);
        $table->addIndex(['field'], 'oro_website_search_decimal_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_website_search_decimal_item_field_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_integer table
     */
    protected function createOroWebsiteSearchIntegerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_integer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'integer', []);
        $table->addIndex(['item_id']);
        $table->addIndex(['field'], 'oro_website_search_integer_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_website_search_integer_item_field_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_datetime table
     */
    protected function createOroWebsiteSearchDatetimeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_datetime');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['item_id']);
        $table->addIndex(['field'], 'oro_website_search_datetime_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_website_search_datetime_item_field_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_website_search_item table
     */
    protected function createOroWebsiteSearchItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight', 'decimal', ['precision' => 8, 'scale' => 4, 'default' => 1]);
        $table->addColumn('changed', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['entity', 'record_id', 'alias'], 'oro_website_search_item_uidx');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity'], 'oro_website_search_item_idxe', []);
        $table->addIndex(['alias'], 'oro_website_search_item_idxa', []);
    }

    /**
     * Create oro_website_search_text table
     */
    protected function createOroWebsiteSearchTextTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_website_search_text');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'text', []);
        $table->addIndex(['item_id']);
        $table->addIndex(['field'], 'oro_website_search_text_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_website_search_text_item_field_idx');
        $table->setPrimaryKey(['id']);

        if ($this->isMysqlPlatform() && !$this->isInnoDBFulltextIndexSupported()) {
            $table->addOption('engine', PdoMysql::ENGINE_MYISAM);
            $queries->addPostQuery(new UseMyIsamEngineQuery('oro_website_search_text'));
        }

        $createFulltextIndexQuery = $this->container->get('oro_website_search.fulltext_index_manager')->getQuery();
        $queries->addPostQuery($createFulltextIndexQuery);
    }

    /**
     * Add oro_website_search_decimal foreign keys.
     */
    protected function addOroWebsiteSearchDecimalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_decimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_website_search_integer foreign keys.
     */
    protected function addOroWebsiteSearchIntegerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_integer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_website_search_datetime foreign keys.
     */
    protected function addOroWebsiteSearchDatetimeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_datetime');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_website_search_text foreign keys.
     */
    protected function addOroWebsiteSearchTextForeignKeys(Schema $schema)
    {
        if (!$this->isMysqlPlatform() || $this->isInnoDBFulltextIndexSupported()) {
            $table = $schema->getTable('oro_website_search_text');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_website_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => null]
            );
        }
    }
}
