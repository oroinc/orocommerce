<?php
namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListManager
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @param EntityManager $manager
     */
    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param AccountUser $accountUser
     *
     * @return bool
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

        return $this->setCurrent($accountUser, $shoppingList);
    }

    /**
     * @param AccountUser  $accountUser
     * @param ShoppingList $shoppingList
     *
     * @return bool
     */
    public function setCurrent(AccountUser $accountUser, ShoppingList $shoppingList)
    {
        $currentList = $this->manager
            ->getRepository('OroB2BShoppingListBundle:ShoppingList')
            ->findCurrentForAccountUser($accountUser);
        if ($currentList instanceof ShoppingList && $currentList !== $shoppingList) {
            $currentList->setCurrent(false);
            $this->manager->persist($currentList);
        }
        $shoppingList->setCurrent(true);
        $this->manager->persist($shoppingList);
        $this->manager->flush();

        return true;
    }
}
