<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds new unique index to table "oro_redirect_slug"
 */
class AddUniqueIndexToRedirectSlug implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_redirect_slug');
        if ($table->hasIndex('oro_redirect_slug_uidx')) {
            return;
        }

        $queries->addPreQuery(new RemoveSlugDuplicatesQuery());
        $table->addUniqueIndex(
            ['organization_id', 'url_hash', 'scopes_hash'],
            'oro_redirect_slug_uidx'
        );
    }

    public function getOrder()
    {
        return 10;
    }
}
