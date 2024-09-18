<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This migration updates the `normalized_search_term_hash` field in the
 * `oro_website_search_result_history` table by computing the MD5 hash
 * of the `search_term` field.
 */
class UpdateNormalizedSearchTermHash implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        /**
         * Please note that there are no duplicates in this table.
         * Only hashes that were built incorrectly need to be fixed.
         *
         * As an example, the hashes for “search term” and “search-term” terms were the same,
         * which caused an error when generating the report.
         *
         * Find more information here:
         * @Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository::actualizeReport
         */
        $sql = <<<SQL
            UPDATE oro_website_search_result_history
            SET normalized_search_term_hash = MD5(search_term)
        SQL;

        $queries->addQuery($sql);
    }
}
