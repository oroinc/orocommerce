<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ShoppingListBundle\Migrations\Schema\OroShoppingListBundleInstaller;

/**
 * Add saved for later list relation to oro_shopping_list_line_item and make shopping_list_id is nullable.
 * Also, create and update indexed for these relations.
 */
class AddSavedForLaterListRelation implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addSavedForLaterListRelation($schema, $queries);
        $this->updateShoppingListRelation($schema, $queries);
    }

    private function addSavedForLaterListRelation(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_shopping_list_line_item');

        if (!$table->hasColumn('saved_for_later_list_id')) {
            $table->addColumn('saved_for_later_list_id', 'integer', ['notnull' => false]);

            $table->addForeignKeyConstraint(
                $schema->getTable('oro_shopping_list'),
                ['saved_for_later_list_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }
    }

    private function updateShoppingListRelation(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_shopping_list_line_item');

        $table->changeColumn('shopping_list_id', ['notnull' => false]);
        $table->dropIndex('oro_shopping_list_line_item_uidx');

        OroShoppingListBundleInstaller::addUniqueIndexToLineItem($queries);
        OroShoppingListBundleInstaller::addExclusiveListIndexToLineItem($queries);
    }
}
