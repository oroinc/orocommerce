<?php

namespace Oro\Bundle\OrderBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

/**
 * Denies access to OrderLineItem entities that belong to not accessible order.
 */
class OrderLineItemAccessRule implements AccessRuleInterface
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
        $criteria->andExpression(new Association('order'));
    }
}
