<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Doctrine\DBAL\Types\IntegerType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration
{
    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_order');

        $table->removeForeignKey('fk_orob2b_order_account_id');

        $table->changeColumn('account_id', ['type' => IntegerType::getType('integer'), 'notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
