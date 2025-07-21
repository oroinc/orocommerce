<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddProjectNameToQuote implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        if (!$table->hasColumn('project_name')) {
            $table->addColumn('project_name', 'string', ['notnull' => false, 'length' => 255]);
        }
    }
}
