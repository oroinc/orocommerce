<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
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
     * @param ManagerRegistry       $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $managerRegistry, TokenStorageInterface $tokenStorage)
    {
        $this->shoppingListEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $this->lineItemEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $this->accountUser = $tokenStorage->getToken()->getUser();
    }

    /**
     * Creates current shopping list
     *
     * @param string $label
     *
     * @return ShoppingList
     */
    public function createCurrent($label = 'Default')
    {
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOwner($this->accountUser)
            ->setOrganization($this->accountUser->getOrganization())
            ->setAccount($this->accountUser->getCustomer())
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

        if ($currentList instanceof ShoppingList && $currentList !== $shoppingList) {
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
        $possibleDuplicate = $repository->findDuplicate($lineItem);
        if ($possibleDuplicate instanceof LineItem && $shoppingList->getId()) {
            $possibleDuplicate->setQuantity($possibleDuplicate->getQuantity() + $lineItem->getQuantity());
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
