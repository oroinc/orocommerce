<?php

namespace Oro\Bundle\ShoppingListBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

/**
 * Denies access to shopping list LineItem entities that belong to not accessible shopping lists.
 */
class ShoppingListItemAccessRule implements AccessRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Association('shoppingList'));
    }
}
