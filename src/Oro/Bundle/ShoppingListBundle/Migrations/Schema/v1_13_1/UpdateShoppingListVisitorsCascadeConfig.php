<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_13_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateExtendRelationDataQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Adds "cascade=['persist']" rule for ShoppingList::visitors association.
 */
class UpdateShoppingListVisitorsCascadeConfig implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new UpdateExtendRelationDataQuery(
            ShoppingList::class,
            ExtendHelper::buildRelationKey(
                CustomerVisitor::class,
                'shoppingLists',
                RelationType::MANY_TO_MANY,
                ShoppingList::class
            ),
            'cascade',
            ['persist']
        ));
        $queries->addQuery(new UpdateEntityConfigFieldValueQuery(
            ShoppingList::class,
            'visitors',
            'extend',
            'cascade',
            ['persist']
        ));
    }
}
