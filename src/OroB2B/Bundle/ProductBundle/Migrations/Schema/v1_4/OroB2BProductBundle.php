<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BProductNameTable($schema);
        $this->createOrob2BProductDescriptionTable($schema);

        $this->addOrob2BProductNameForeignKeys($schema);
        $this->addOrob2BProductDescriptionForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BProductNameTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_name');
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['product_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_ba57d521eb576e89');
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BProductDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_description');
        $table->addColumn('description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_416a3679eb576e89');
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BProductNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_fallback_locale_value'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BProductDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_description');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_fallback_locale_value'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['description_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
