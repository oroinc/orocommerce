<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListListener
{
    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $args->getEntity();
        if ($shoppingList instanceof ShoppingList && $shoppingList->isCurrent()) {
            $this->setNewCurrent($args->getEntityManager(), $shoppingList->getAccountUser());
        }
    }

    /**
     * @param EntityManager $em
     * @param AccountUser   $accountUser
     */
    protected function setNewCurrent(EntityManager $em, AccountUser $accountUser)
    {
        $shoppingList = $em->getRepository('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->findLatestForAccountUserExceptCurrent($accountUser);

        if ($shoppingList instanceof ShoppingList) {
            $shoppingList->setCurrent(true);
            $em->flush($shoppingList);
        }
    }
}
