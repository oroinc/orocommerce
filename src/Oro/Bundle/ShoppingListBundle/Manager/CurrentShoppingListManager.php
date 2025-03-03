<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Handles logic related to getting/creating shopping lists for currently logged in customer users,
 * including customer visitors.
 */
class CurrentShoppingListManager
{
    public function __construct(
        private ShoppingListManager $shoppingListManager,
        private GuestShoppingListManager $guestShoppingListManager,
        private CurrentShoppingListStorage $currentShoppingListStorage,
        private ManagerRegistry $doctrine,
        private AclHelper $aclHelper,
        private TokenAccessorInterface $tokenAccessor,
        private ConfigManager $configManager
    ) {
    }

    public function createCurrent(?string $label = ''): ShoppingList
    {
        $shoppingList = $this->shoppingListManager->create(true, $label);
        $this->setCurrent($this->getCustomerUser(), $shoppingList);

        return $shoppingList;
    }

    public function setCurrent(CustomerUser $customerUser, ShoppingList $shoppingList): void
    {
        $customerUserId = $customerUser->getId();
        if (!$customerUserId) {
            throw new \LogicException('The customer user ID must not be empty.');
        }
        $shoppingListId = $shoppingList->getId();
        if (!$shoppingListId) {
            throw new \LogicException('The shopping list ID must not be empty.');
        }

        $this->currentShoppingListStorage->set($customerUserId, $shoppingListId);
        $shoppingList->setCurrent(true);
    }

    public function getCurrent(bool $create = false, ?string $label = ''): ?ShoppingList
    {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
                return $create
                    ? $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor()
                    : $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
            }

            return null;
        }

        return $this->getCurrentShoppingList($create, $label);
    }

    public function getForCurrentUser(
        ?int $shoppingListId = null,
        bool $create = false,
        ?string $label = ''
    ): ?ShoppingList {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
                return $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor();
            }

            return null;
        }

        $shoppingList = null;
        if ($shoppingListId) {
            $shoppingList = $this->getShoppingListRepository()
                ->findByUserAndId($this->aclHelper, $shoppingListId);
        }
        if (null === $shoppingList) {
            $shoppingList = $this->getCurrentShoppingList();
        }

        if (!$this->shoppingListAvailableForCurrentUser($shoppingList)) {
            $shoppingList = $create ? $this->createCurrent($label) : null;
        }

        return $shoppingList;
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingListsWithCurrentFirst(array $sortCriteria = []): array
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListsForCustomerVisitor();
        }

        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = [];
        $currentShoppingList = $this->getCurrentShoppingList();
        if (null !== $currentShoppingList) {
            $shoppingLists = $this->getShoppingListRepository()
                ->findByUser($this->aclHelper, $sortCriteria, $currentShoppingList);
            $shoppingLists = array_merge([$currentShoppingList], $shoppingLists);
        }

        return $shoppingLists;
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingListsForCustomerUserWithCurrentFirst(
        int $customerUserId,
        array $sortCriteria = []
    ): array {
        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = [];
        $currentShoppingList = $this->getCurrentShoppingList();
        if (null !== $currentShoppingList) {
            if ($currentShoppingList->getCustomerUser()->getId() !== $customerUserId) {
                $currentShoppingList = null;
            }

            $shoppingLists = $this->getShoppingListRepository()
                ->findByCustomerUserId($customerUserId, $this->aclHelper, $sortCriteria, $currentShoppingList);

            if ($currentShoppingList) {
                $shoppingLists = array_merge([$currentShoppingList], $shoppingLists);
            } elseif ($shoppingLists) {
                $currentShoppingList = reset($shoppingLists);
                $this->setCurrent($currentShoppingList->getCustomerUser(), $currentShoppingList);
            }
        }

        return $shoppingLists;
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingLists(array $sortCriteria = []): array
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListsForCustomerVisitor();
        }

        return $this->getShoppingListRepository()
            ->findByUser($this->aclHelper, $sortCriteria);
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingListsByCustomerUser(CustomerUser $customerUser, array $sortCriteria = []): array
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListsForCustomerVisitor();
        }

        return $this->getShoppingListRepository()
            ->findByCustomerUserId($customerUser->getId(), $this->aclHelper, $sortCriteria);
    }

    public function isCurrentShoppingListEmpty(): bool
    {
        $currentShoppingList = $this->getCurrent();

        return
            null === $currentShoppingList
            || $currentShoppingList->getLineItems()->count() === 0;
    }

    private function getCurrentShoppingList(bool $create = false, ?string $label = ''): ?ShoppingList
    {
        $shoppingList = null;

        $customerUser = $this->getCustomerUser();
        if (null !== $customerUser) {
            $currentListId = $this->currentShoppingListStorage->get($customerUser->getId());
            if (null !== $currentListId) {
                $shoppingList = $this->getShoppingListRepository()
                    ->findByUserAndId($this->aclHelper, $currentListId);
            }
            if (null === $shoppingList) {
                $shoppingList = $this->getShoppingListRepository()
                    ->findAvailableForCustomerUser($this->aclHelper);
            }
            if (null !== $shoppingList) {
                if ($shoppingList->getId() !== $currentListId) {
                    $this->setCurrent($customerUser, $shoppingList);
                } elseif (!$shoppingList->isCurrent()) {
                    $shoppingList->setCurrent(true);
                }
            } elseif ($create) {
                $shoppingList = $this->createCurrent($label);
            }
        }

        return $shoppingList;
    }

    private function getCustomerUser(): ?CustomerUser
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser ? $user : null;
    }

    private function getShoppingListRepository(): ShoppingListRepository
    {
        return $this->doctrine->getRepository(ShoppingList::class);
    }

    private function isShowAllInShoppingListWidget(): bool
    {
        return (bool)$this->configManager->get('oro_shopping_list.show_all_in_shopping_list_widget');
    }

    private function shoppingListAvailableForCurrentUser(?ShoppingList $shoppingList): bool
    {
        if (null === $shoppingList) {
            return false;
        }

        if ($this->isShowAllInShoppingListWidget()) {
            return true;
        }

        if (!$shoppingList->getCustomerUser() || !$this->getCustomerUser()) {
            return true;
        }

        return $shoppingList->getCustomerUser()->getId() === $this->getCustomerUser()->getId();
    }
}
