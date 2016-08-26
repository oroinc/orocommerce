<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProductBundle implements Migration
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_SHORT_DESCRIPTION_TABLE_NAME = 'orob2b_product_short_desc';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'orob2b_fallback_locale_value';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroProductShortDescriptionTable($schema);
        $this->addOroProductShortDescriptionForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('short_description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['short_description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['short_description_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
