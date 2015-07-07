<?php
namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class ShoppingListManager
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @param ManagerRegistry $manager
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManagerForClass('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
    }

    /**
     * @param AccountUser $accountUser
     */
    public function createCurrent(AccountUser $accountUser)
    {
        $shoppingList = new ShoppingList();
        $shoppingList
            ->setOwner($accountUser)
            ->setOrganization($accountUser->getOrganization())
            ->setAccount($accountUser->getCustomer())
            ->setAccountUser($accountUser)
            ->setLabel('Default');

        $this->setCurrent($accountUser, $shoppingList);
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
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
    }
}
