<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    /** @var CurrentShoppingListStorage */
    private $currentShoppingListStorage;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AclHelper */
    private $aclHelper;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        ShoppingListManager $shoppingListManager,
        GuestShoppingListManager $guestShoppingListManager,
        CurrentShoppingListStorage $currentShoppingListStorage,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        TokenAccessorInterface $tokenAccessor,
        ConfigManager $configManager
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->guestShoppingListManager = $guestShoppingListManager;
        $this->currentShoppingListStorage = $currentShoppingListStorage;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->configManager = $configManager;
    }

    /**
     * Creates current shopping list
     *
     * @param string $label
     *
     * @return ShoppingList
     */
    public function createCurrent($label = '')
    {
        $shoppingList = $this->shoppingListManager->create(true, $label);
        $this->setCurrent($this->getCustomerUser(), $shoppingList);

        return $shoppingList;
    }

    public function setCurrent(CustomerUser $customerUser, ShoppingList $shoppingList)
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

    /**
     * @param bool   $create
     * @param string $label
     *
     * @return ShoppingList|null
     */
    public function getCurrent($create = false, $label = '')
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $create
                ? $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor()
                : $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
        }

        return $this->getCurrentShoppingList($create, $label);
    }

    /**
     * @param int $shoppingListId
     *
     * @return ShoppingList|null
     */
    public function getForCurrentUser($shoppingListId = null, $create = false, $label = '')
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor();
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
     * @param array $sortCriteria
     *
     * @return ShoppingList[]
     */
    public function getShoppingListsWithCurrentFirst(array $sortCriteria = [])
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
     * @param int $customerUserId
     * @param array $sortCriteria
     *
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
     * @param array $sortCriteria
     *
     * @return ShoppingList[]
     */
    public function getShoppingLists(array $sortCriteria = [])
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListsForCustomerVisitor();
        }

        return $this->getShoppingListRepository()
            ->findByUser($this->aclHelper, $sortCriteria);
    }

    /**
     * @param CustomerUser $customerUser
     * @param array $sortCriteria
     *
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

    /**
     * @return bool
     */
    public function isCurrentShoppingListEmpty()
    {
        $currentShoppingList = $this->getCurrent();

        return
            null === $currentShoppingList
            || $currentShoppingList->getLineItems()->count() === 0;
    }

    /**
     * @param bool   $create
     * @param string $label
     *
     * @return ShoppingList|null
     */
    private function getCurrentShoppingList($create = false, $label = '')
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

    /**
     * @return CustomerUser|null
     */
    private function getCustomerUser()
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser ? $user : null;
    }

    /**
     * @return ShoppingListRepository
     */
    private function getShoppingListRepository()
    {
        return $this->doctrine
            ->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class);
    }

    /**
     * @return bool
     */
    private function isShowAllInShoppingListWidget(): bool
    {
        return (bool)$this->configManager->get('oro_shopping_list.show_all_in_shopping_list_widget');
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return bool
     */
    private function shoppingListAvailableForCurrentUser(?ShoppingList $shoppingList): bool
    {
        if (is_null($shoppingList)) {
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
