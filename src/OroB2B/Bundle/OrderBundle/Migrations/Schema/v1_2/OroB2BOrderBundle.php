<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_order_address');
        $table->addColumn('from_external_source', 'boolean', ['notnull' => true, 'default' => false]);
    }
}
