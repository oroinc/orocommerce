<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;

/**
 * Provides information about associations to which an access check should be ignored
 * when the current security context represents a visitor and the checkout feature is enabled for visitors.
 */
class GuestCheckoutAssociationAccessExclusionProvider implements AssociationAccessExclusionProviderInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    private string $entityClass;
    /** @var string[] */
    private array $excludedAssociations;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        string $entityClass,
        array $excludedAssociations
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->entityClass = $entityClass;
        $this->excludedAssociations = $excludedAssociations;
    }

    /**
     * {@inheritDoc}
     */
    public function isIgnoreAssociationAccessCheck(string $entityClass, string $associationName): bool
    {
        if (!is_a($entityClass, $this->entityClass, true)) {
            return false;
        }
        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return false;
        }

        return \in_array($associationName, $this->excludedAssociations, true);
    }
}
