<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddRawSlugIndex implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_redirect_slug');
        $table->addColumn('parameters_hash', 'string', ['length' => 32, 'notnull' => false]);
        $table->addIndex(['parameters_hash'], 'oro_redirect_slug_parameters_hash_idx');
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery('UPDATE oro_redirect_slug SET parameters_hash = MD5(route_parameters)')
        );
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 10;
    }
}
