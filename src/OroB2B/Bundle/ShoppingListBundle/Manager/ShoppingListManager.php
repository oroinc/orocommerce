<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListManager
{
    /**
     * @var ObjectManager
     */
    protected $shoppingListEm;

    /**
     * @var ObjectManager
     */
    protected $lineItemEm;

    /**
     * @var AccountUser
     */
    protected $accountUser;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param ManagerRegistry       $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     * @param Translator            $translator
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $tokenStorage,
        Translator $translator
    ) {
        $this->shoppingListEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $this->lineItemEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $this->accountUser = $tokenStorage->getToken()->getUser();
        $this->translator = $translator;
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
        $label = $label !== '' ? $label : $this->translator->trans('orob2b.shoppinglist.default.label');

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOwner($this->accountUser)
            ->setOrganization($this->accountUser->getOrganization())
            ->setAccount($this->accountUser->getAccount())
            ->setAccountUser($this->accountUser)
            ->setLabel($label);

        $this->setCurrent($this->accountUser, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->shoppingListEm->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentList = $shoppingListRepository->findCurrentForAccountUser($accountUser);

        if ($currentList instanceof ShoppingList && $currentList->getId() !== $shoppingList->getId()) {
            $currentList->setCurrent(false);
        }
        $shoppingList->setCurrent(true);

        $this->shoppingListEm->persist($shoppingList);
        $this->shoppingListEm->flush();
    }

    /**
     * @param LineItem          $lineItem
     * @param ShoppingList|null $shoppingList
     * @param bool|true         $flush
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true)
    {
        $lineItem->setShoppingList($shoppingList);
        /** @var LineItemRepository $repository */
        $repository = $this->lineItemEm->getRepository('OroB2BShoppingListBundle:LineItem');
        $duplicate = $repository->findDuplicate($lineItem);
        if ($duplicate instanceof LineItem && $shoppingList->getId()) {
            $duplicate->setQuantity($duplicate->getQuantity() + $lineItem->getQuantity());
        } else {
            $shoppingList->addLineItem($lineItem);
            $this->lineItemEm->persist($lineItem);
        }

        if ($flush) {
            $this->lineItemEm->flush();
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
        /** @var ShoppingListRepository $repository */
        $repository = $this->shoppingListEm->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $shoppingList = null === $shoppingListId
            ? $repository->findCurrentForAccountUser($this->accountUser)
            : $repository->findByUserAndId($this->accountUser, $shoppingListId);

        if (!($shoppingList instanceof ShoppingList)) {
            $shoppingList = $this->createCurrent();
        }

        return $shoppingList;
    }
}
