<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_2_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Runs {@see ChangeDataInEmptySearchResultPageSystemConfigQuery} to get rid of serialized content node in the system
 * config setting "oro_web_catalog.empty_search_result_page".
 */
class ChangeDataInEmptySearchResultPageSystemConfig implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new ChangeDataInEmptySearchResultPageSystemConfigQuery());
    }
}
