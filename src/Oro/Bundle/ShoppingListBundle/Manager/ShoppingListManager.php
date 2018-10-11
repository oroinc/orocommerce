<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handles logic related to shopping list and line item manipulations (create, remove, etc.).
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListManager
{
    /**
     * @var CustomerUser
     */
    private $customerUser;

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
     * @var ProductVariantAvailabilityProvider
     */
    protected $productVariantProvider;

    /**
     * @var GuestShoppingListManager
     */
    private $guestShoppingListManager;

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
     * @param ProductVariantAvailabilityProvider $productVariantProvider
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
        Cache $cache,
        ProductVariantAvailabilityProvider $productVariantProvider
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
        $this->productVariantProvider = $productVariantProvider;
    }

    /**
     * @param GuestShoppingListManager $guestShoppingListManager
     */
    public function setGuestShoppingListManager(GuestShoppingListManager $guestShoppingListManager)
    {
        $this->guestShoppingListManager = $guestShoppingListManager;
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
            ->setOrganization($this->getCustomerUser()->getOrganization())
            ->setCustomer($this->getCustomerUser()->getCustomer())
            ->setCustomerUser($this->getCustomerUser())
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
        $this->setCurrent($this->getCustomerUser(), $shoppingList);

        return $shoppingList;
    }

    /**
     * @param CustomerUser  $customerUser
     * @param ShoppingList $shoppingList
     */
    public function setCurrent(CustomerUser $customerUser, ShoppingList $shoppingList)
    {
        $this->cache->save($customerUser->getId(), $shoppingList->getId());
        $shoppingList->setCurrent(true);
    }

    /**
     * @param LineItem $lineItem
     * @param ShoppingList $shoppingList
     * @param bool $flush
     * @param bool $concatNotes
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true, $concatNotes = false)
    {
        $func = function (LineItem $duplicate) use ($lineItem, $concatNotes) {
            $this->mergeLineItems($lineItem, $duplicate, $concatNotes);
        };

        $this
            ->prepareLineItem($lineItem, $shoppingList)
            ->handleLineItem($lineItem, $shoppingList, $func);

        if ($flush) {
            $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');
            $em->flush();
        }
    }

    /**
     * @param LineItem $lineItem
     * @param ShoppingList $shoppingList
     */
    public function updateLineItem(LineItem $lineItem, ShoppingList $shoppingList)
    {
        $func = function (LineItem $duplicate) use ($lineItem) {
            if ($lineItem->getQuantity() > 0) {
                $this->updateLineItemQuantity($lineItem, $duplicate);
            } else {
                $this->removeLineItem($duplicate);
            }
        };

        $this
            ->prepareLineItem($lineItem, $shoppingList)
            ->handleLineItem($lineItem, $shoppingList, $func);

        $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');
        $em->flush();
    }

    /**
     * @param int $lineItemId
     * @param ShoppingList $shoppingList
     * @return LineItem|null
     */
    public function getLineItem(int $lineItemId, ShoppingList $shoppingList): ?LineItem
    {
        $lineItems = $shoppingList->getLineItems();
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getId() === $lineItemId) {
                return $lineItem;
            }
        }

        return null;
    }

    /**
     * @param LineItem $lineItem
     * @param LineItem $duplicate
     * @param bool     $concatNotes
     */
    protected function mergeLineItems(LineItem $lineItem, LineItem $duplicate, $concatNotes)
    {
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
    }

    /**
     * Set new quantity for $duplicate line item based on quantity value from $lineItem
     *
     * @param LineItem $lineItem
     * @param LineItem $duplicate
     */
    protected function updateLineItemQuantity(LineItem $lineItem, LineItem $duplicate)
    {
        $quantity = $this->rounding->roundQuantity(
            $lineItem->getQuantity(),
            $duplicate->getUnit(),
            $duplicate->getProduct()
        );
        $duplicate->setQuantity($quantity);
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

        $products = [];
        if ($product->isConfigurable()) {
            $products = $this->productVariantProvider->getSimpleProductsByVariantFields($product);
        }
        $products[] = $product;

        $lineItems = $repository->getItemsByShoppingListAndProducts($shoppingList, $products);

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
        $lineItemsCount = count($lineItems);
        foreach ($lineItems as $iteration => $lineItem) {
            $flush = $iteration % $batchSize === 0 || $lineItemsCount === $iteration + 1;
            $this->addLineItem($lineItem, $shoppingList, $flush);
        }

        return $lineItemsCount;
    }

    /**
     * @param int $shoppingListId
     *
     * @return ShoppingList
     */
    public function getForCurrentUser($shoppingListId = null)
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
        }
        $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $repository */
        $repository = $em->getRepository('OroShoppingListBundle:ShoppingList');

        $shoppingList = null;
        if ($shoppingListId) {
            $shoppingList = $repository->findByUserAndId($this->aclHelper, $shoppingListId, $this->getWebsiteId());
        }

        if (!$shoppingList instanceof ShoppingList) {
            $shoppingList = $this->getCurrent(true);
        }

        return $shoppingList;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param bool $create
     * @param string $label
     * @return ShoppingList
     */
    public function getCurrent($create = false, $label = '')
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
        }
        /* @var $repository ShoppingListRepository */
        $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');
        if (!$this->getCustomerUser()) {
            return null;
        }
        $currentListId = $this->cache->fetch($this->getCustomerUser()->getId());
        $shoppingList = null;
        if ($currentListId) {
            $shoppingList = $repository->findByUserAndId($this->aclHelper, $currentListId, $this->getWebsiteId());
        }
        if (!$shoppingList) {
            $shoppingList  = $repository->findAvailableForCustomerUser($this->aclHelper, false, $this->getWebsiteId());
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
     * @return bool
     */
    public function isCurrentShoppingListEmpty()
    {
        $shoppingLists = $this->getShoppingListsWithCurrentFirst();

        if (count($shoppingLists) != 1) {
            return false;
        }

        return $shoppingLists[0]->getLineItems()->count() == 0;
    }

    /**
     * @param array $sortCriteria
     * @return ShoppingList[]
     */
    public function getShoppingLists(array $sortCriteria = [])
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return [$this->guestShoppingListManager->getShoppingListForCustomerVisitor()];
        }

        /* @var $repository ShoppingListRepository */
        $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');

        return $repository->findByUser($this->aclHelper, $sortCriteria, null, $this->getWebsiteId());
    }

    /**
     * @param array $sortCriteria
     * @return ShoppingList[]
     */
    public function getShoppingListsWithCurrentFirst(array $sortCriteria = [])
    {
        if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
            return [$this->guestShoppingListManager->getShoppingListForCustomerVisitor()];
        }
        $shoppingLists = [];
        $currentShoppingList = $this->getCurrent();
        if ($currentShoppingList) {
            /* @var $repository ShoppingListRepository */
            $repository = $this->getRepository('OroShoppingListBundle:ShoppingList');
            $shoppingLists = $repository->findByUser(
                $this->aclHelper,
                $sortCriteria,
                $currentShoppingList,
                $this->getWebsiteId()
            );
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
     * @return string|CustomerUser
     */
    protected function getCustomerUser()
    {
        if (!$this->customerUser) {
            $token = $this->tokenStorage->getToken();
            if ($token && ($customerUser = $token->getUser()) instanceof CustomerUser) {
                $this->customerUser = $customerUser;
            }
        }

        if (!$this->customerUser || !is_object($this->customerUser)) {
            return null;
        }

        return $this->customerUser;
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

    /**
     * @param ShoppingList $shoppingList
     * @param string $label
     *
     * @return ShoppingList
     */
    public function edit($shoppingList, $label = '')
    {
        if ($this->tokenStorage->getToken()->getUser() instanceof CustomerUser) {
            $shoppingList
                ->setOrganization($this->getCustomerUser()->getOrganization())
                ->setCustomer($this->getCustomerUser()->getCustomer())
                ->setCustomerUser($this->getCustomerUser())
                ->setWebsite($this->websiteManager->getCurrentWebsite())
                ->setLabel($label !== '' ? $label : $shoppingList->getLabel());
        }

        return $shoppingList;
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function removeLineItems($shoppingList)
    {
        /** @var EntityManager $lineItemManager */
        $lineItemManager = $this->managerRegistry->getManagerForClass(LineItem::class);
        $lineItems = $shoppingList->getLineItems();

        foreach ($lineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $lineItemManager->remove($lineItem);
        }
        $this->totalManager->recalculateTotals($shoppingList, false);
        $lineItemManager->flush();
    }

    /**
     * @param LineItem $lineItem
     * @param ShoppingList $shoppingList
     *
     * @return ShoppingListManager
     */
    private function prepareLineItem(LineItem $lineItem, ShoppingList $shoppingList)
    {
        $this->ensureProductTypeAllowed($lineItem);

        $lineItem->setShoppingList($shoppingList);

        if (null === $lineItem->getCustomerUser() && $shoppingList->getCustomerUser()) {
            $lineItem->setCustomerUser($shoppingList->getCustomerUser());
        }

        if (null === $lineItem->getOrganization() && $shoppingList->getOrganization()) {
            $lineItem->setOrganization($shoppingList->getOrganization());
        }

        return $this;
    }

    /**
     * @param LineItem $lineItem
     * @param ShoppingList $shoppingList
     * @param \Closure $func
     *
     * @return ShoppingListManager
     */
    private function handleLineItem(LineItem $lineItem, ShoppingList $shoppingList, \Closure $func)
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass('OroShoppingListBundle:LineItem');

        /** @var LineItemRepository $repository */
        $repository = $em->getRepository('OroShoppingListBundle:LineItem');
        $duplicate = $repository->findDuplicate($lineItem);
        if ($duplicate instanceof LineItem && $shoppingList->getId()) {
            $func($duplicate);
            $em->remove($lineItem);
        } elseif ($lineItem->getQuantity() > 0) {
            $shoppingList->addLineItem($lineItem);
            $em->persist($lineItem);
        }

        $this->totalManager->recalculateTotals($shoppingList, false);

        return $this;
    }

    /**
     * @return int|null
     */
    protected function getWebsiteId()
    {
        if (!$website = $this->websiteManager->getCurrentWebsite()) {
            return null;
        }
        return $website->getId();
    }
}
