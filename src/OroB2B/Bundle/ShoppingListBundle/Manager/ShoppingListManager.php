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
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManagerForClass('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
    }

    /**
     * @param AccountUser $accountUser
     * @param string      $label
     *
     * @return ShoppingList
     */
    public function createCurrent(AccountUser $accountUser, $label = 'Default')
    {
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
     * @param boolean      $flush
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList, $flush = true)
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

        if ($flush) {
            $this->manager->flush();
        }
    }
}
