<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createCheckoutWorkflowStateTable($schema);
    }

    /**
     * Create orob2b_checkout_workflow_state table
     * @param Schema $schema
     */
    protected function createCheckoutWorkflowStateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_checkout_workflow_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('token', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('state_data', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(
            ['entity_id', 'entity_class', 'token'],
            'orob2b_checkout_workflow_state_unique_id_class_token_idx'
        );
        $table->setPrimaryKey(['id']);
    }
}
