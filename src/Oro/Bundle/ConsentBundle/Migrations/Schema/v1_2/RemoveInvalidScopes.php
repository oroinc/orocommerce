<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes invalid scopes related to the WebCatalog nodes which are used in the consents
 */
class RemoveInvalidScopes implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        /**
         * We could not use such kind of query because it will be incompatible with the mysql:
         * DELETE FROM oro_scope WHERE id NOT IN (SELECT s.id FROM oro_scope s
         * LEFT JOIN oro_web_catalog_variant_scope sv ON sv.scope_id = s.id
         * LEFT JOIN oro_web_catalog_node_scope ns ON ns.scope_id = s.id
         * WHERE s.webcatalog_id IS NOT NULL AND sv.variant_id IS NULL AND ns.node_id IS NULL)
         *
         * So we should invert it to make it work in all supported RDBMS
         */
        $sql = <<<SQL
            DELETE FROM oro_scope WHERE id NOT IN (
                SELECT vs.scope_id FROM oro_web_catalog_variant_scope as vs
                UNION
                SELECT ns.scope_id FROM oro_web_catalog_node_scope as ns
            )
            AND webcatalog_id IS NOT NULL
SQL;
        $queries->addPostQuery($sql);
    }
}
