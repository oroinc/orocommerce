<?php

namespace Oro\Bundle\InventoryBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

/**
 * Denies access to InventoryLevel entities that belong to not accessible Product.
 */
class InventoryLevelAccessRule implements AccessRuleInterface
{
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Association('product'));
    }
}
