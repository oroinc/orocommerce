<?php

namespace Oro\Bundle\OrderBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;

/**
 * Removes AccessDenied expression for User relation to be able to show created_by column in the Orders grid
 * in case override_created_by_acl option is set
 */
class OrderCreatedByAccessRule implements AccessRuleInterface
{
    public function isApplicable(Criteria $criteria): bool
    {
        $options = $criteria->getOption(AclAccessRule::CONDITION_DATA_BUILDER_CONTEXT, []);

        return ($options['override_created_by_acl'] ?? false)
            && $criteria->getAlias() === 'created_by'
            && $criteria->getPermission() === BasicPermission::VIEW
            && $criteria->getExpression() instanceof AccessDenied;
    }

    public function process(Criteria $criteria): void
    {
        $criteria->setExpression(new Comparison(true, Comparison::EQ, true));
    }
}
