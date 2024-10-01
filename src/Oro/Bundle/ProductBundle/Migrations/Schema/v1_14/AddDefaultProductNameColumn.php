<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDefaultProductNameColumn implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 10;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('name_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['name'], 'idx_oro_product_default_name');
        $table->addIndex(['name_uppercase'], 'idx_oro_product_default_name_uppercase');
    }
}
