<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Deletes related incomplete checkouts before shopping list is deleted.
 */
class ShoppingListListener
{
    public function __construct(
        private ManagerRegistry $registry,
        private string $checkoutClassName,
        private string $checkoutSourceClassName
    ) {
    }

    public function preRemove(ShoppingList $entity): void
    {
        $checkoutSources = $this->getRepository($this->checkoutSourceClassName)->findBy(['shoppingList' => $entity]);
        if (!$checkoutSources) {
            return;
        }

        /** @var Checkout[] $checkout */
        $checkouts = $this->getRepository($this->checkoutClassName)->findBy(['source' => $checkoutSources]);
        if (!$checkouts) {
            return;
        }

        $em = $this->registry->getManagerForClass($this->checkoutClassName);
        $flushNeeded = false;
        foreach ($checkouts as $checkout) {
            if (!$checkout->isCompleted()) {
                $flushNeeded = true;
                $em->remove($checkout);
            }

            // Since we have a postFlush event for the checkout workflow, a situation may arise where after deleting
            // the shopping list, it remains in unitOfWork. After flushing it in the postFlush event, the removed
            // shopping list is added as a new one.
            $checkout->getSource()->clear();
        }

        if ($flushNeeded) {
            $em->flush();
        }
    }

    private function getRepository(string $className): ObjectRepository
    {
        return $this->registry->getRepository($className);
    }
}
