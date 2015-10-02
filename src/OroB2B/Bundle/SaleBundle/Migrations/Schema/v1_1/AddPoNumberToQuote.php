<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AddPoNumberToQuote implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add description to Quote
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
    }
}
