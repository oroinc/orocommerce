<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_10_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates the unique constraint on oro_website_search_term_report table
 * to include business_unit_owner_id column.
 */
class UpdateSearchTermReportUniqueConstraint implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPreQuery(<<<SQL
            ALTER TABLE oro_website_search_term_report
            DROP CONSTRAINT IF EXISTS website_search_term_report_term_unq
        SQL);

        $queries->addPostQuery(<<<SQL
            ALTER TABLE oro_website_search_term_report
            ADD CONSTRAINT "website_search_term_report_term_unq"
            UNIQUE ("search_date", "normalized_search_term_hash", "business_unit_owner_id")
        SQL);
    }
}
