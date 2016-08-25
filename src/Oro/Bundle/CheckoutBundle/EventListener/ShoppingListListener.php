<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

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
    public function preRemove($entity)
    {
        $checkoutSource = $this->getRepository($this->checkoutSourceClassName)->findOneBy(['shoppingList' => $entity]);
        if (!$checkoutSource) {
            return;
        }

        $checkout = $this->getRepository($this->checkoutClassName)->findOneBy(['source' => $checkoutSource]);
        if (!$checkout) {
            return;
        }

        $em = $this->getEntityManager($this->checkoutClassName);
        $em->remove($checkout);
        $em->flush($checkout);
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
