<?php

namespace Oro\Bundle\ShoppingListBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Denies access to {@see ProductKitItemLineItem} entities that belong to not accessible {@see LineItem}.
 */
class ProductKitItemLineItemAccessRule implements AccessRuleInterface
{
    #[\Override]
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    #[\Override]
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Association('lineItem'));
    }
}
