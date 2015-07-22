<?php
namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListManager
{
    /** @var EntityManager */
    protected $manager;
    /** @var SecurityContext */
    protected $securityContext;

    /**
     * @param EntityManager $entityManager
     * @param SecurityContext $securityContext
     */
    public function __construct(EntityManager $entityManager, SecurityContext $securityContext)
    {
        $this->manager = $entityManager;
        $this->securityContext = $securityContext;
    }

    /**
     * Creates current shopping list
     *
     * @return ShoppingList
     */
    public function createCurrent()
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityContext->getToken()->getUser();
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOwner($accountUser)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getCustomer())
            ->setAccountUser($accountUser)
            ->setLabel('Default');

        return $this->setCurrent($accountUser, $shoppingList);
    }

    /**
     * @param AccountUser $accountUser
     * @param ShoppingList $shoppingList
     *
     * @return ShoppingList
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->manager->getRepository('OroB2BShoppingListBundle:ShoppingList');
        $currentList = $shoppingListRepository->findCurrentForAccountUser($accountUser);
        if ($currentList instanceof ShoppingList && $currentList !== $shoppingList) {
            $currentList->setCurrent(false);
            $this->manager->persist($currentList);
        }
        $shoppingList->setCurrent(true);
        $this->manager->persist($shoppingList);
        $this->manager->flush();

        return $shoppingList;
    }

    /**
     * @param LineItem $lineItem
     * @param ShoppingList|null $shoppingList
     * @param bool|true $flush
     */
    public function addLineItem(LineItem $lineItem, ShoppingList $shoppingList, $flush = true)
    {
        $lineItem->setShoppingList($shoppingList);
        /** @var LineItemRepository $repository */
        $repository = $this->manager->getRepository('OroB2BShoppingListBundle:LineItem');
        if (
            $shoppingList->getId()
            && ($possibleDuplicate = $repository->findDuplicate($lineItem)) instanceof LineItem
        ) {
            $possibleDuplicate->setQuantity($possibleDuplicate->getQuantity() + $lineItem->getQuantity());
            $this->manager->persist($possibleDuplicate);
        } else {
            $shoppingList->addLineItem($lineItem);
            $this->manager->persist($lineItem);
        }

        if ($flush) {
            $this->manager->flush();
        }
    }
}
