<?php

namespace Oro\Bundle\OrderBundle\Acl\AccessRule;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

/**
 * Denies access to {@see OrderProductKitItemLineItem} entities that belong to not accessible {@see OrderLineItem}.
 */
class OrderProductKitItemLineItemAccessRule implements AccessRuleInterface
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
