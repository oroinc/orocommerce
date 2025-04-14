<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_10_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * Adding denormalized columns to store the day, month, quarter, and year of the search date.
 * This is part of an optimization that helps speed up filtering, grouping, and sorting by dates.
 */
class AddDateFieldsToSearchTermReport implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_website_search_term_report');

        if (!$table->hasColumn('search_date_day')) {
            $table->addColumn('search_date_day', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('search_date_month')) {
            $table->addColumn('search_date_month', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('search_date_quarter')) {
            $table->addColumn('search_date_quarter', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('search_date_year')) {
            $table->addColumn('search_date_year', 'integer', ['notnull' => false]);
        }

        $queries->addPostQuery(new SqlMigrationQuery(
            <<<SQL
                UPDATE oro_website_search_term_report
                SET
                    search_date_day = EXTRACT(DAY FROM search_date AT TIME ZONE 'UTC'),
                    search_date_month = EXTRACT(MONTH FROM search_date AT TIME ZONE 'UTC'),
                    search_date_quarter = EXTRACT(QUARTER FROM search_date AT TIME ZONE 'UTC'),
                    search_date_year = EXTRACT(YEAR FROM search_date AT TIME ZONE 'UTC')
                WHERE search_date IS NOT NULL;
            SQL
        ));

        $queries->addPostQuery(new SqlMigrationQuery(
            <<<SQL
                ALTER TABLE oro_website_search_term_report
                ALTER COLUMN search_date_day SET NOT NULL,
                ALTER COLUMN search_date_month SET NOT NULL,
                ALTER COLUMN search_date_quarter SET NOT NULL,
                ALTER COLUMN search_date_year SET NOT NULL;
            SQL
        ));

        $queries->addPostQuery(new SqlMigrationQuery(
            <<<SQL
                CREATE INDEX IF NOT EXISTS website_search_term_report_organization_id_idx 
                ON oro_website_search_term_report 
                USING btree (id, organization_id);
            SQL
        ));
    }
}
