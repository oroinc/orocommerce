<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Create table to store search results history records.
 */
class CreateSearchResultHistoryTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroWebsiteSearchResultHistoryTable($schema, $queries);
        $this->createOroWebsiteSearchSearchTermReportTable($schema, $queries);

        $this->addOroWebsiteSearchResultHistoryForeignKeys($schema);
        $this->addOroWebsiteSearchSearchTermReportForeignKeys($schema);
    }

    private function createOroWebsiteSearchResultHistoryTable(Schema $schema, QueryBag $queries): void
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

    private function addOroWebsiteSearchResultHistoryForeignKeys(Schema $schema): void
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
