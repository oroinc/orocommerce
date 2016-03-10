<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemListener
{
    /**
     * @var ServiceLink
     */
    protected $shoppingListManagerLink;

    /**
     * @var ShoppingList[]
     */
    protected $shoppingLists;

    /**
     * @param ServiceLink $shoppingListManagerLink
     */
    public function __construct(ServiceLink $shoppingListManagerLink)
    {
        $this->shoppingListManagerLink = $shoppingListManagerLink;
        $this->shoppingLists = [];
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $uow = $event->getEntityManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof LineItem && $entity->getShoppingList()) {
                $this->addShoppingList($entity->getShoppingList());
            }
        }
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $shoppingLists = $this->getShoppingLists();
        if ($shoppingLists) {
            foreach ($shoppingLists as $shoppingList) {
                $this->getShoppingListManager()->recalculateSubtotals($shoppingList, false);
            }
            $this->shoppingLists = [];
            $event->getEntityManager()->flush();
        }
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function addShoppingList($shoppingList)
    {
        $this->shoppingLists[$shoppingList->getId()] = $shoppingList;
    }

    /**
     * @return ShoppingList[]
     */
    public function getShoppingLists()
    {
        return $this->shoppingLists;
    }

    /**
     * @return ShoppingListManager
     */
    protected function getShoppingListManager()
    {
        return $this->shoppingListManagerLink->getService();
    }
}
