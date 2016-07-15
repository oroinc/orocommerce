<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCheckoutWorkflowState implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_checkout_workflow_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('hash', 'string', ['length' => 13]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('state_data', 'array', ['comment' => '(DC2Type:array)']);
        $table->addUniqueIndex(['entity_id', 'entity_class', 'hash'], 'unique_state');
        $table->setPrimaryKey(['id']);
    }
}
