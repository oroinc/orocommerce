<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveUniqueIndexAddDeferrableUniqueConstraintToRedirectSlug implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $tableName = 'oro_redirect_slug';
        $oldIndexName = 'oro_redirect_slug_uidx';
        $newIndexName = 'oro_redirect_slug_deferrable_uidx';

        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);

        $this->dropIndex($table, $oldIndexName);
        $this->addDeferrableConstraint($table, $queries, $newIndexName);
    }

    private function dropIndex(Table $table, string $indexName): void
    {
        if ($table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }
    }

    private function addDeferrableConstraint(Table $table, QueryBag $queries, string $indexName): void
    {
        if (!$table->hasIndex($indexName)) {
            $queries->addPostQuery(
                sprintf(
                    'ALTER TABLE %s ADD CONSTRAINT %s ' .
                    'UNIQUE(organization_id, url_hash, scopes_hash) DEFERRABLE INITIALLY DEFERRED',
                    $table->getName(),
                    $indexName
                )
            );
        }
    }
}
