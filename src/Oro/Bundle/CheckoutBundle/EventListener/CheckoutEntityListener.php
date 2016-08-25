<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CheckoutEntityListener
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $checkoutClassName;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param RegistryInterface $doctrine
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(RegistryInterface $doctrine, UserCurrencyManager $userCurrencyManager)
    {
        $this->doctrine = $doctrine;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param string $checkoutClassName
     */
    public function setCheckoutClassName($checkoutClassName)
    {
        if (!is_a($checkoutClassName, 'Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface', true)) {
            throw new \InvalidArgumentException(
                'Checkout class must implement Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface'
            );
        }

        $this->checkoutClassName = $checkoutClassName;
    }

    /**
     * @param CheckoutEntityEvent $event
     */
    public function onCreateCheckoutEntity(CheckoutEntityEvent $event)
    {
        $this->setCheckoutToEvent($event, $this->startCheckout($event));
    }

    /**
     * @param CheckoutEntityEvent $event
     * @return null|CheckoutInterface
     */
    public function onGetCheckoutEntity(CheckoutEntityEvent $event)
    {
        $this->setCheckoutToEvent($event, $this->findExistingCheckout($event));
    }

    /**
     * @param CheckoutEntityEvent $event
     * @param CheckoutInterface|null $checkout
     */
    protected function setCheckoutToEvent(CheckoutEntityEvent $event, CheckoutInterface $checkout = null)
    {
        if ($checkout) {
            $event->setCheckoutEntity($checkout);
            $event->stopPropagation();
        }
    }

    /**
     * @param CheckoutEntityEvent $event
     * @return null|CheckoutInterface
     */
    protected function findExistingCheckout(CheckoutEntityEvent $event)
    {
        if ($event->getCheckoutId()) {
            /** @var Checkout $checkout */
            $checkout = $this->getRepository()->find($event->getCheckoutId());
        } elseif ($event->getSource() && $event->getSource()->getId()) {
            /** @var Checkout $checkout */
            $checkout = $this->getRepository()->findOneBy(['source' => $event->getSource()]);
        }

        return isset($checkout) ? $this->actualizeCheckoutCurrency($checkout) : null;
    }

    /**
     * @param Checkout $checkout
     * @return Checkout
     */
    protected function actualizeCheckoutCurrency(Checkout $checkout)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass('OroCheckoutBundle:Checkout');
        $checkout->setCurrency($this->userCurrencyManager->getUserCurrency());
        $em->persist($checkout);
        $em->flush($checkout);

        return $checkout;
    }

    /**
     * @param CheckoutEntityEvent $event
     * @return CheckoutInterface
     */
    protected function startCheckout(CheckoutEntityEvent $event)
    {
        if (!$event->getSource()) {
            return null;
        }
        
        $checkout = $this->createCheckoutEntity();
        $checkout->setSource($event->getSource());

        return $checkout;
    }

    /**
     * @return string
     */
    protected function getCheckoutClassName()
    {
        return $this->checkoutClassName;
    }

    /**
     * @return CheckoutInterface
     */
    protected function createCheckoutEntity()
    {
        $checkoutClassName = $this->getCheckoutClassName();

        return new $checkoutClassName();
    }

    /**
     * @return EntityManager|null
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->doctrine->getManagerForClass($this->getCheckoutClassName());
        }
        return $this->manager;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->getManager()->getRepository($this->getCheckoutClassName());
        }

        return $this->repository;
    }
}
