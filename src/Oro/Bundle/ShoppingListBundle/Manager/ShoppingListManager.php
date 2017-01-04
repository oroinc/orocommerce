<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ShoppingListManager
{
    /**
     * @var AccountUser
     */
    private $accountUser;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var QuantityRoundingService
     */
    protected $rounding;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ShoppingListTotalManager
     */
    protected $totalManager;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     * @param QuantityRoundingService $rounding
     * @param UserCurrencyManager $userCurrencyManager
     * @param WebsiteManager $websiteManager
     * @param ShoppingListTotalManager $totalManager
     * @param AclHelper $aclHelper
     * @param Cache $cache
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        QuantityRoundingService $rounding,
        UserCurrencyManager $userCurrencyManager,
        WebsiteManager $websiteManager,
        ShoppingListTotalManager $totalManager,
        AclHelper $aclHelper,
        Cache $cache
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->websiteManager = $websiteManager;
        $this->totalManager = $totalManager;
        $this->aclHelper = $aclHelper;
        $this->cache = $cache;
    }

    /**
     * Creates new shopping list
     *
     * @param string $label
     * @param bool $flush
     * @return ShoppingList
     */
    public function create($flush = false, $label = '')
    {
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOrganization($this->getAccountUser()->getOrganization())
            ->setAccount($this->getAccountUser()->getAccount())
            ->setAccountUser($this->getAccountUser())
            ->setWebsite($this->websiteManager->getCurrentWebsite());

        $shoppingList->setLabel($label !== '' ? $label : $this->translator->trans('oro.shoppinglist.default.label'));

        if ($flush) {
            /** @var EntityManager $em */
            $em = $this->managerRegistry->getManagerForClass(ShoppingList::class);
            $em->persist($shoppingList);
            $em->flush($shoppingList);
        }

        return $shoppingList;
    }

    /**
     * Creates current shopping list
     *
     * @param string $label
     * @return ShoppingList
     */
    public function createCurrent($label = '')
    {
        $shoppingList = $this->create(true, $label);
        $this->setCurrent($this->getAccountUser(), $shoppingList);

        return $shoppingList;
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        $this->cache->save($accountUser->getId(), $shoppingList->getId());
        $shoppingList->setCurrent(true);
    }

    /**
     * @param LineItem          $lineItem
     * @param ShoppingList|null $shoppingList
     * @param bool|true         $flush
     * @param bool|false        $concatNotes
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true, $concatNotes = false)
    {
        $this->ensureProductTypeAllowed($lineItem);
        $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');
        $lineItem->setShoppingList($shoppingList);
        /** @var LineItemRepository $repository */
        $repository = $em->getRepository('OroShoppingListBundle:LineItem');
        $duplicate = $repository->findDuplicate($lineItem);
        if ($duplicate instanceof LineItem && $shoppingList->getId()) {
            $quantity = $this->rounding->roundQuantity(
                $duplicate->getQuantity() + $lineItem->getQuantity(),
                $duplicate->getUnit(),
                $duplicate->getProduct()
            );
            $duplicate->setQuantity($quantity);

            if ($concatNotes) {
                $notes = trim(implode(' ', [$duplicate->getNotes(), $lineItem->getNotes()]));
                $duplicate->setNotes($notes);
            }
        } else {
            $shoppingList->addLineItem($lineItem);
            $em->persist($lineItem);
        }

        $this->totalManager->recalculateTotals($shoppingList, false);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param bool $flush
     * @return int Number of removed line items
     */
    public function removeProduct(ShoppingList $shoppingList, Product $product, $flush = true)
    {
        $objectManager = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');
        /** @var LineItemRepository $repository */
        $repository = $objectManager->getRepository('OroShoppingListBundle:LineItem');

        $lineItems = $repository->getItemsByShoppingListAndProducts($shoppingList, [$product]);

        foreach ($lineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $objectManager->remove($lineItem);
        }

        $this->totalManager->recalculateTotals($shoppingList, false);
        if ($lineItems && $flush) {
            $objectManager->flush();
        }

        return count($lineItems);
    }

    /**
     * @param LineItem $lineItem
     */
    public function removeLineItem(LineItem $lineItem)
    {
        $objectManager = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');
        $objectManager->remove($lineItem);
        $shoppingList = $lineItem->getShoppingList();
        $shoppingList->removeLineItem($lineItem);
        $this->totalManager->recalculateTotals($lineItem->getShoppingList(), false);
        $objectManager->flush();
    }

    /**
     * @param array        $lineItems
     * @param ShoppingList $shoppingList
     * @param int          $batchSize
     *
     * @return int
     */
    public function bulkAddLineItems(array $lineItems, ShoppingList $shoppingList, $batchSize)
    {
        $iteration = 0;
        foreach ($lineItems as $iteration => $lineItem) {
            $flush = $iteration % $batchSize === 0 || count($lineItems) === $iteration + 1;
            $this->addLineItem($lineItem, $shoppingList, $flush);
        }

        return $iteration + 1;
    }

    /**
     * @param int $shoppingListId
     *
     * @return ShoppingList
     */
    public function getForCurrentUser($shoppingListId = null)
    {
        $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $repository */
        $repository = $em->getRepository('OroShoppingListBundle:ShoppingList');
        $shoppingList = null;
        if ($shoppingListId) {
            $shoppingList = $repository->findByUserAndId($this->aclHelper, $shoppingListId);
        }

        if (!$shoppingList instanceof ShoppingList) {
            $shoppingList = $this->getCurrent(true);
        }

        return $shoppingList;
    }

    /**
     * @param bool $create
     * @param string $label
     * @return ShoppingList
     */
    public function getCurrent($create = false, $label = '')
    {
        /* @var $repository ShoppingListRepository */
        $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');
        if (!$this->getAccountUser()) {
            return null;
        }
        $currentListId = $this->cache->fetch($this->getAccountUser()->getId());
        $shoppingList = null;
        if ($currentListId) {
            $shoppingList = $repository->findByUserAndId($this->aclHelper, $currentListId);
        }
        if (!$shoppingList) {
            $shoppingList  = $repository->findAvailableForAccountUser($this->aclHelper);
        }
        if ($create && !$shoppingList instanceof ShoppingList) {
            $label = $this->translator->trans($label ?: 'oro.shoppinglist.default.label');

            $shoppingList = $this->createCurrent();
            $shoppingList->setLabel($label);
        }
        if ($shoppingList) {
            $shoppingList->setCurrent(true);
        }

        return $shoppingList;
    }

    /**
     * @param array $sortCriteria
     * @return array
     */
    public function getShoppingLists(array $sortCriteria = [])
    {
        /* @var $repository ShoppingListRepository */
        $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');

        return $repository->findByUser($this->aclHelper, $sortCriteria);
    }

    /**
     * @param array $sortCriteria
     * @return array
     */
    public function getShoppingListsWithCurrentFirst(array $sortCriteria = [])
    {
        $shoppingLists = [];
        $currentShoppingList = $this->getCurrent();
        if ($currentShoppingList) {
            /* @var $repository ShoppingListRepository */
            $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');
            $shoppingLists = $repository->findByUser($this->aclHelper, $sortCriteria, $currentShoppingList);
            $shoppingLists = array_merge([$currentShoppingList], $shoppingLists);
        }
        return $shoppingLists;
    }

    /**
     * @param string $class
     * @return ObjectRepository
     */
    protected function getRepository($class)
    {
        return $this->managerRegistry->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @return string|AccountUser
     */
    protected function getAccountUser()
    {
        if (!$this->accountUser) {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                $this->accountUser = $token->getUser();
            }
        }

        if (!$this->accountUser || !is_object($this->accountUser)) {
            return null;
        }

        return $this->accountUser;
    }

    /**
     * @param LineItem $lineItem
     * @throws \InvalidArgumentException
     */
    private function ensureProductTypeAllowed(LineItem $lineItem)
    {
        $product = $lineItem->getProduct();

        if ($product && !$product->isSimple()) {
            throw new \InvalidArgumentException('Can not save not simple product');
        }
    }
}
