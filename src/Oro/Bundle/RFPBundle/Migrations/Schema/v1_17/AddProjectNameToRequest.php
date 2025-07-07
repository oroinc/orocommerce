<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddProjectNameToRequest implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_request');
        if (!$table->hasColumn('project_name')) {
            $table->addColumn('project_name', 'string', ['notnull' => false, 'length' => 255]);
        }
    }
}
