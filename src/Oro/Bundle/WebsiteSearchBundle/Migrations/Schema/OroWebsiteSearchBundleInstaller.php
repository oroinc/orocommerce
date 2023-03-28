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
        return 'v1_7';
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
        $this->createOroWebsiteSearchResultHistoryTable($schema, $queries);
        $this->createOroWebsiteSearchSearchTermReportTable($schema, $queries);

        /** Foreign keys generation **/
        $this->addOroWebsiteSearchDecimalForeignKeys($schema);
        $this->addOroWebsiteSearchIntegerForeignKeys($schema);
        $this->addOroWebsiteSearchDatetimeForeignKeys($schema);
        $this->addOroWebsiteSearchTextForeignKeys($schema);
        $this->addOroWebsiteSearchResultHistoryForeignKeys($schema);
        $this->addOroWebsiteSearchSearchTermReportForeignKeys($schema);
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

    protected function createOroWebsiteSearchResultHistoryTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_website_search_result_history');
        $table->addColumn('id', 'guid');
        $table->addColumn('normalized_search_term_hash', 'string', ['length' => 32]);
        $table->addColumn('result_type', 'string', ['length' => 32]);
        $table->addColumn('results_count', 'integer');
        $table->addColumn('search_session', 'string', ['notnull' => false, 'length' => 36]);
        $table->addColumn('search_term', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_visitor_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addIndex(['normalized_search_term_hash'], 'website_search_result_history_sterm_hash_idx');

        $queries->addPostQuery(
            'CREATE INDEX website_search_result_history_term_lower_idx'
            . ' ON oro_website_search_result_history (LOWER("search_term"))'
        );

        $queries->addPostQuery(
            'ALTER TABLE oro_website_search_result_history'
            . ' ADD CONSTRAINT "website_search_result_history_search_session_unq" UNIQUE ("search_session")'
        );
    }

    private function createOroWebsiteSearchSearchTermReportTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_website_search_term_report');
        $table->addColumn('id', 'guid');
        $table->addColumn('normalized_search_term_hash', 'string', ['length' => 32]);
        $table->addColumn('search_term', 'string', ['length' => 255]);
        $table->addColumn('times_searched', 'integer');
        $table->addColumn('times_returned_results', 'integer');
        $table->addColumn('times_empty', 'integer');
        $table->addColumn('search_date', 'date');
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addIndex(['search_date'], 'website_search_term_report_date_idx');

        $queries->addPostQuery(
            'ALTER TABLE oro_website_search_term_report'
            . ' ADD CONSTRAINT "website_search_term_report_term_unq"'
            . ' UNIQUE ("search_date", "normalized_search_term_hash")'
        );
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

    protected function addOroWebsiteSearchResultHistoryForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_website_search_result_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function addOroWebsiteSearchSearchTermReportForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_website_search_term_report');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
