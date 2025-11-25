<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Resolver;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Determines if RFQ validation group is applicable for a shopping list.
 */
class ShoppingListToRequestQuoteValidationGroupResolver implements
    ShoppingListValidationGroupResolverInterface,
    FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public const string TYPE = 'rfq';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function isApplicable(): bool
    {
        if (!$this->isFeaturesEnabled()) {
            return false;
        }

        if (!$this->authorizationChecker->isGranted('oro_rfp_frontend_request_create')) {
            return false;
        }

        return true;
    }

    public function getValidationGroupName(): string
    {
        return 'datagrid_line_items_data_for_rfq';
    }
}
