<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\SecurityContext;

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
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param SecurityContext $securityContext
     */
    public function __construct(ManagerRegistry $managerRegistry, SecurityContext $securityContext)
    {
        $this->shoppingListEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
        $this->lineItemEm = $managerRegistry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $this->securityContext = $securityContext;
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
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityContext->getToken()->getUser();
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOwner($accountUser)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getCustomer())
            ->setAccountUser($accountUser)
            ->setLabel($label);

        $this->setCurrent($accountUser, $shoppingList);

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
        $user = $this->securityContext->getToken()->getUser();
        /** @var ShoppingListRepository $repository */
        $repository = $this->shoppingListEm->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $shoppingList = null === $shoppingListId
            ? $repository->findCurrentForAccountUser($user)
            : $repository->findByUserAndId($user, $shoppingListId);

        if (!$shoppingList instanceof ShoppingList) {
            $shoppingList = $this->createCurrent();
        }

        return $shoppingList;
    }
}
