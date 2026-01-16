<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Acl\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;

/**
 * Denies access to an @link LineItem when an access to an associated entity is denied.
 */
class LineItemAssociationAwareAccessRule implements AccessRuleInterface
{
    public function __construct(
        private readonly string $shoppingListAssociation,
        private readonly string $savedForLaterAssociation
    ) {
    }

    #[\Override]
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    #[\Override]
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new Association($this->shoppingListAssociation),
            new Association($this->savedForLaterAssociation)
        ]));
    }
}
