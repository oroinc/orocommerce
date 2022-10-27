<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add scopes_hash field for the Slug to use it in unique constraint.
 */
class AddScopesHashField implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_redirect_slug');
        if ($table->hasColumn('scopes_hash')) {
            return;
        }

        $table->addColumn('scopes_hash', 'string', ['length' => 32, 'notnull' => false]);
        $queries->addPostQuery(new RemoveSlugDuplicatesQuery());
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
