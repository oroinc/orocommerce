<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Resolver;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Determines if the checkout validation group is applicable for a shopping list.
 */
class ShoppingListToCheckoutValidationGroupResolver implements ShoppingListValidationGroupResolverInterface
{
    public const string TYPE = 'checkout';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly IsWorkflowStartFromShoppingListAllowed $isWorkflowStartFromShoppingListAllowed
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function isApplicable(): bool
    {
        if (!$this->authorizationChecker->isGranted(
            'CREATE',
            'entity:commerce@Oro\Bundle\CheckoutBundle\Entity\Checkout'
        )) {
            return false;
        }

        if (!$this->isWorkflowStartFromShoppingListAllowed->isAllowedForAny()) {
            return false;
        }

        return true;
    }

    public function getValidationGroupName(): string
    {
        return 'datagrid_line_items_data_for_checkout';
    }
}
