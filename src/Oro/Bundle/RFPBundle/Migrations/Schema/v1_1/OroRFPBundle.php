<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRFPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);

        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\RFPBundle\Entity\Request',
                'grouping',
                'groups',
                null
            )
        );
    }
}
