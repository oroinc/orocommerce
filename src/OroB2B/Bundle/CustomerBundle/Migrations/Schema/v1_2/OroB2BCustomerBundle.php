<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCustomerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroB2BAuditFieldTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroB2BAuditFieldTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_audit_field');
        $table->addColumn('visible', 'boolean', ['default' => '1']);
        $table->addColumn('old_datetimetz', 'datetimetz', ['notnull' => false]);
        $table->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn(
            'old_simplearray',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('old_jsonarray', 'json_array', ['notnull' => false]);
        $table->addColumn('new_datetimetz', 'datetimetz', ['notnull' => false]);
        $table->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn(
            'new_simplearray',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('new_jsonarray', 'json_array', ['notnull' => false]);
    }
}
