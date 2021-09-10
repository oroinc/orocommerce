<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds prototype fields to redirect table and fill them.
 */
class AddRedirectPrototypeFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateRedirrectTable($schema);

        $queries->addPostQuery(new FillRedirectPrototypesQuery());
    }

    private function updateRedirrectTable(Schema $schema)
    {
        $table = $schema->getTable('oro_redirect');
        if (!$table->hasColumn('redirect_from_prototype')) {
            $table->addColumn('redirect_from_prototype', 'string', ['length' => 255, 'notnull' => false]);
        }

        if (!$table->hasColumn('redirect_to_prototype')) {
            $table->addColumn('redirect_to_prototype', 'string', ['length' => 255, 'notnull' => false]);
        }

        if (!$table->hasIndex('idx_oro_redirect_redirect_from_prototype')) {
            $table->addIndex(['redirect_from_prototype'], 'idx_oro_redirect_redirect_from_prototype', []);
        }
    }
}
