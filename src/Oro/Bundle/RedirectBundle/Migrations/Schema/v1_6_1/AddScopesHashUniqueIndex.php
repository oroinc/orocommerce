<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds unique constraint by scopes_hash and url_hash fields.
 */
class AddScopesHashUniqueIndex implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_redirect_slug');
        if ($table->hasIndex('oro_redirect_slug_scopes_idx')) {
            return;
        }

        $table->modifyColumn('scopes_hash', ['notnull' => true]);
        $table->addUniqueIndex(
            ['url_hash', 'scopes_hash', 'route_name', 'parameters_hash'],
            'oro_redirect_slug_scopes_idx'
        );
    }

    #[\Override]
    public function getOrder()
    {
        return 10;
    }
}
