<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

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

    /** @var Cache */
    private $cache;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AclHelper */
    private $aclHelper;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var WebsiteManager */
    private $websiteManager;

    /**
     * @param ShoppingListManager      $shoppingListManager
     * @param GuestShoppingListManager $guestShoppingListManager
     * @param Cache                    $cache
     * @param ManagerRegistry          $doctrine
     * @param AclHelper                $aclHelper
     * @param TokenAccessorInterface   $tokenAccessor
     * @param WebsiteManager           $websiteManager
     */
    public function __construct(
        ShoppingListManager $shoppingListManager,
        GuestShoppingListManager $guestShoppingListManager,
        Cache $cache,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        TokenAccessorInterface $tokenAccessor,
        WebsiteManager $websiteManager
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->guestShoppingListManager = $guestShoppingListManager;
        $this->cache = $cache;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->websiteManager = $websiteManager;
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

    /**
     * @param CustomerUser $customerUser
     * @param ShoppingList $shoppingList
     */
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

        $this->cache->save($customerUserId, $shoppingListId);
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
     * @return ShoppingList
     */
    public function getForCurrentUser($shoppingListId = null)
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor();
        }

        $shoppingList = null;
        if ($shoppingListId) {
            $shoppingList = $this->getShoppingListRepository()
                ->findByUserAndId($this->aclHelper, $shoppingListId, $this->getWebsiteId());
        }
        if (null === $shoppingList) {
            $shoppingList = $this->getCurrentShoppingList(true);
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
                ->findByUser($this->aclHelper, $sortCriteria, $currentShoppingList, $this->getWebsiteId());
            $shoppingLists = array_merge([$currentShoppingList], $shoppingLists);
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
            ->findByUser($this->aclHelper, $sortCriteria, null, $this->getWebsiteId());
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
            $currentListId = $this->cache->fetch($customerUser->getId());
            if (false !== $currentListId) {
                $shoppingList = $this->getShoppingListRepository()
                    ->findByUserAndId($this->aclHelper, $currentListId, $this->getWebsiteId());
            }
            if (null === $shoppingList) {
                $shoppingList = $this->getShoppingListRepository()
                    ->findAvailableForCustomerUser($this->aclHelper, false, $this->getWebsiteId());
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
     * @return int|null
     */
    private function getWebsiteId()
    {
        $website = $this->websiteManager->getCurrentWebsite();

        return null !== $website ? $website->getId() : null;
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
}
