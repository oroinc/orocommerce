<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

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
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $checkoutClassName
     */
    public function setCheckoutClassName($checkoutClassName)
    {
        $this->checkoutClassName = $checkoutClassName;
    }

    /**
     * @param string $checkoutSourceClassName
     * @return $this
     */
    public function setCheckoutSourceClassName($checkoutSourceClassName)
    {
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
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }
}
