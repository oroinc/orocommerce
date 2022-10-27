<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes unique index "oro_redirect_slug_scopes_idx"
 */
class RemoveScopesHashUniqueIndex implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_redirect_slug');
        if (!$table->hasIndex('oro_redirect_slug_scopes_idx')) {
            return;
        }

        $table->dropIndex('oro_redirect_slug_scopes_idx');
    }

    public function getOrder()
    {
        return 0;
    }
}
