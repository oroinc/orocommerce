<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

/**
 * Recalculates all shopping list totals.
 */
class ShoppingListTotalsDemoDataFixturesListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var ShoppingListTotalManager */
    private $shoppingListTotalManager;

    public function __construct(
        ManagerRegistry $registry,
        ShoppingListTotalManager $shoppingListTotalManager
    ) {
        $this->registry = $registry;
        $this->shoppingListTotalManager = $shoppingListTotalManager;
    }

    public function onPostLoad(MigrationDataFixturesEvent $event): void
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $lists = $this->registry->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->findBy([]);

        foreach ($lists as $list) {
            $this->shoppingListTotalManager->recalculateTotals($list, true);
        }
    }
}
