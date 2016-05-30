<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

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
     * @var TotalProcessorProvider
     */
    protected $totalProvider;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $lineItemNotPricedSubtotalProvider;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     * @param QuantityRoundingService $rounding
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     * @param LocaleSettings $localeSettings
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        QuantityRoundingService $rounding,
        TotalProcessorProvider $totalProvider,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        LocaleSettings $localeSettings,
        WebsiteManager $websiteManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->totalProvider = $totalProvider;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->localeSettings = $localeSettings;
        $this->websiteManager = $websiteManager;
    }

    /**
     * Creates new shopping list
     *
     * @return ShoppingList
     */
    public function create()
    {
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOrganization($this->getAccountUser()->getOrganization())
            ->setAccount($this->getAccountUser()->getAccount())
            ->setAccountUser($this->getAccountUser())
            ->setCurrency($this->localeSettings->getCurrency())
            ->setWebsite($this->websiteManager->getCurrentWebsite());

        return $shoppingList;
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
        $shoppingList = $this->create();
        $shoppingList->setLabel($label !== '' ? $label : $this->translator->trans('orob2b.shoppinglist.default.label'));

        $this->setCurrent($this->getAccountUser(), $shoppingList);

        return $shoppingList;
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $em->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentList = $shoppingListRepository->findCurrentForAccountUser($accountUser);

        if ($currentList instanceof ShoppingList && $currentList->getId() !== $shoppingList->getId()) {
            $currentList->setCurrent(false);
        }
        $shoppingList->setCurrent(true);

        $em->persist($shoppingList);
        $em->flush();
    }

    /**
     * @param LineItem          $lineItem
     * @param ShoppingList|null $shoppingList
     * @param bool|true         $flush
     * @param bool|false        $concatNotes
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true, $concatNotes = false)
    {
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $lineItem->setShoppingList($shoppingList);
        /** @var LineItemRepository $repository */
        $repository = $em->getRepository('OroB2BShoppingListBundle:LineItem');
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

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param bool $flush
     * @return int                       Number of removed line items
     */
    public function removeProduct(ShoppingList $shoppingList, Product $product, $flush = true)
    {
        $objectManager = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $repository = $objectManager->getRepository('OroB2BShoppingListBundle:LineItem');

        $lineItems = $repository->getItemsByShoppingListAndProduct($shoppingList, $product);

        foreach ($lineItems as $lineItem) {
            $shoppingList->removeLineItem($lineItem);
            $objectManager->remove($lineItem);
        }

        if ($lineItems && $flush) {
            $objectManager->flush();
        }

        return count($lineItems);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param bool|true $flush
     */
    public function recalculateSubtotals(ShoppingList $shoppingList, $flush = true)
    {
        $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotal($shoppingList);
        $total = $this->totalProvider->getTotal($shoppingList);

        if ($subtotal) {
            $shoppingList->setSubtotal($subtotal->getAmount());
        }
        if ($total) {
            $shoppingList->setTotal($total->getAmount());
        }
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $em->persist($shoppingList);

        if ($flush) {
            $em->flush();
        }
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
        $em = $this->managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        /** @var ShoppingListRepository $repository */
        $repository = $em->getRepository('OroB2BShoppingListBundle:ShoppingList');
        if ($shoppingListId === null) {
            $shoppingList = $repository->findCurrentForAccountUser($this->getAccountUser());
        } else {
            $shoppingList = $repository->findByUserAndId($this->getAccountUser(), $shoppingListId);
        }

        if (!$shoppingList instanceof ShoppingList) {
            $shoppingList = $this->createCurrent();
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
        $repository = $this->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $shoppingList = $repository->findCurrentForAccountUser($this->getAccountUser());

        if ($create && !$shoppingList instanceof ShoppingList) {
            $label = $this->translator->trans($label ?: 'orob2b.shoppinglist.default.label');

            $shoppingList = $this->create();
            $shoppingList->setLabel($label);
        }

        return $shoppingList;
    }

    /**
     * @return array
     */
    public function getShoppingLists()
    {
        $accountUser = $this->getAccountUser();
        /* @var $repository ShoppingListRepository */
        $repository = $this->getRepository('OroB2BShoppingListBundle:ShoppingList');

        return $repository->findByUser($accountUser);
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
            throw new AccessDeniedException();
        }

        return $this->accountUser;
    }
}
