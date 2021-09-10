<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Provides a set of methods to check the limit for the number of shopping lists for a customer user or a visitor.
 */
class ShoppingListLimitManager
{
    private ConfigManager $configManager;
    private TokenStorageInterface $tokenStorage;
    private DoctrineHelper $doctrineHelper;
    private WebsiteManager $websiteManager;
    private ?bool $isCreateEnabled = null;
    private ?bool $isCreateEnabledForCustomerUser = null;
    private ?bool $isOnlyOneEnabled = null;

    public function __construct(
        ConfigManager $configManager,
        TokenStorageInterface $tokenStorage,
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager
    ) {
        $this->configManager = $configManager;
        $this->tokenStorage = $tokenStorage;
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteManager = $websiteManager;
    }

    public function resetState(): void
    {
        $this->isCreateEnabled = null;
        $this->isCreateEnabledForCustomerUser = null;
        $this->isOnlyOneEnabled = null;
    }

    /**
     * Checks whether the configured limit for the number of shopping lists is reached
     * for the current logged in customer user or a visitor.
     */
    public function isReachedLimit(): bool
    {
        return !$this->isCreateEnabled();
    }

    /**
     * Checks whether a new shopping list can be created by the current logged in customer user or a visitor.
     */
    public function isCreateEnabled(): bool
    {
        $user = $this->getUser();
        if (null === $user) {
            // the creation of shopping lists is not allowed for visitors
            // and it does not depend on the configured limit
            return false;
        }

        if (null === $this->isCreateEnabled) {
            $shoppingListLimit = $this->getShoppingListLimit();
            $this->isCreateEnabled =
                !$shoppingListLimit
                || $this->countUserShoppingLists($user) < $shoppingListLimit;
        }

        return $this->isCreateEnabled;
    }

    /**
     * Checks whether it is allowed to create a new shopping list for the given customer user.
     */
    public function isCreateEnabledForCustomerUser(CustomerUser $user): bool
    {
        if (null === $this->isCreateEnabledForCustomerUser) {
            $shoppingListLimit = $this->getShoppingListLimit($user->getWebsite());
            $this->isCreateEnabledForCustomerUser =
                !$shoppingListLimit
                || $this->countUserShoppingLists($user) < $shoppingListLimit;
        }

        return $this->isCreateEnabledForCustomerUser;
    }

    /**
     * Checks whether only one shopping list is available for the current logged in customer user or a visitor.
     */
    public function isOnlyOneEnabled(): bool
    {
        $user = $this->getUser();
        if (null === $user) {
            return true;
        }

        if (null === $this->isOnlyOneEnabled) {
            $this->isOnlyOneEnabled =
                1 === $this->getShoppingListLimit()
                && 1 === $this->countUserShoppingLists($user);
        }

        return $this->isOnlyOneEnabled;
    }

    /**
     * Gets the maximum number of shopping lists that can be created by the current logged in user or a visitor.
     */
    public function getShoppingListLimitForUser(): int
    {
        $user = $this->getUser();
        if (null === $user) {
            return 1;
        }

        return $this->getShoppingListLimit($user->getWebsite());
    }

    private function countUserShoppingLists(AbstractUser $user): int
    {
        /** @var ShoppingListRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ShoppingList::class);

        return $repository->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId(),
            $this->websiteManager->getCurrentWebsite()
        );
    }

    private function getShoppingListLimit(Website $website = null): int
    {
        return (int)$this->configManager->get('oro_shopping_list.shopping_list_limit', false, false, $website);
    }

    private function getUser(): ?CustomerUser
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof TokenInterface) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof CustomerUser) {
            return null;
        }

        return $user;
    }
}
