<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Deletes related incomplete checkouts before shopping list is deleted.
 */
class ShoppingListListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $checkoutClassName;

    /** @var string */
    protected $checkoutSourceClassName;

    /**
     * @param ManagerRegistry $registry
     * @param string $checkoutClassName
     * @param string $checkoutSourceClassName
     */
    public function __construct(ManagerRegistry $registry, $checkoutClassName, $checkoutSourceClassName)
    {
        $this->registry = $registry;
        $this->checkoutClassName = $checkoutClassName;
        $this->checkoutSourceClassName = $checkoutSourceClassName;
    }

    /**
     * @param object $entity
     */
    public function preRemove($entity): void
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

        $em = $this->getEntityManager($this->checkoutClassName);
        $flushNeeded = false;
        foreach ($checkouts as $checkout) {
            if (!$checkout->isCompleted()) {
                $flushNeeded = true;
                $em->remove($checkout);
            }
        }

        if ($flushNeeded) {
            $em->flush();
        }
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getEntityManager($className)
    {
        return $this->registry->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return EntityRepository
     */
    protected function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }
}
