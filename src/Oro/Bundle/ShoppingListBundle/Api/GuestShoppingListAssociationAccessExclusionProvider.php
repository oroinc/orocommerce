<?php

namespace Oro\Bundle\ShoppingListBundle\Api;

use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides information about associations to which an access check should be ignored
 * when the current security context represents a visitor.
 */
class GuestShoppingListAssociationAccessExclusionProvider implements AssociationAccessExclusionProviderInterface
{
    private TokenStorageInterface $tokenStorage;
    private string $entityClass;
    /** @var string[] */
    private array $excludedAssociations;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        string $entityClass,
        array $excludedAssociations
    ) {
        $this->tokenStorage = $tokenStorage;
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
        if (!$this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            return false;
        }

        return \in_array($associationName, $this->excludedAssociations, true);
    }
}
