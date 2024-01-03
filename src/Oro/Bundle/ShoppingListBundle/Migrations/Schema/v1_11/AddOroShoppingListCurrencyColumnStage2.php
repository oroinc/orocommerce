<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Sets shopping list currency attribute value based on first shopping list total or default currency.
 * Sets currency as nullable=false
 */
class AddOroShoppingListCurrencyColumnStage2 implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new AddOroShoppingListCurrencyColumnStage2Query('USD'));
        $table = $schema->getTable('oro_shopping_list');
        $table->getColumn('currency')->setNotnull(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
