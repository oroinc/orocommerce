<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroWebsiteSearchBundleInstaller implements Installation, ContainerAwareInterface, DatabasePlatformAwareInterface
{
    use ContainerAwareTrait;
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
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

        $query = $this->container->get('oro_website_search.fulltext_index_manager')->getQuery();
        $queries->addQuery($query);
    }

    /**
     * Create oro_website_search_decimal table
     * @param Schema $schema
     */
    protected function createOroWebsiteSearchDecimalTable(Schema $schema)
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
    protected function createOroWebsiteSearchIntegerTable(Schema $schema)
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
    protected function createOroWebsiteSearchDatetimeTable(Schema $schema)
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
     * @param QueryBag $queries
     * @throws ServiceNotFoundException
     */
    protected function createOroWebsiteSearchTextTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_website_search_text');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'text', []);
        $table->addIndex(['item_id']);
        $table->setPrimaryKey(['id']);

        if ($this->platform->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            $table->addOption('engine', PdoMysql::ENGINE_MYISAM);
        }
    }

    /**
     * Add oro_website_search_decimal foreign keys.
     * @param Schema $schema
     */
    protected function addOroWebsiteSearchDecimalForeignKeys(Schema $schema)
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
    protected function addOroWebsiteSearchIntegerForeignKeys(Schema $schema)
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
    protected function addOroWebsiteSearchDatetimeForeignKeys(Schema $schema)
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
    protected function addOroWebsiteSearchTextForeignKeys(Schema $schema)
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
