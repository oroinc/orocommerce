<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShoppingListBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateShoppingListTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateShoppingListTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shopping_list');
        $table->dropColumn('currency');
        $table->dropColumn('subtotal');
        $table->dropColumn('total');
    }
}
