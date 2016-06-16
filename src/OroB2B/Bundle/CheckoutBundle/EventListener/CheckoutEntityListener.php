<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * While implementing custom checkout, alternative checkout entity can be set
 * This Event Listener can be used as base for replacement
 */
class CheckoutEntityListener
{
    const START_TRANSITION_DEFINITION = '__start__';

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

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
     * @var string
     */
    protected $checkoutType = '';

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param WorkflowManager $workflowManager
     * @param RegistryInterface $doctrine
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(
        WorkflowManager $workflowManager,
        RegistryInterface $doctrine,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->workflowManager = $workflowManager;
        $this->doctrine = $doctrine;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param string $checkoutClassName
     */
    public function setCheckoutClassName($checkoutClassName)
    {
        if (!is_a($checkoutClassName, 'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface', true)) {
            throw new \InvalidArgumentException(
                'Checkout class must implement OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface'
            );
        }

        $this->checkoutClassName = $checkoutClassName;
    }

    /**
     * @param string $checkoutType
     */
    public function setCheckoutType($checkoutType)
    {
        $this->checkoutType = $checkoutType;
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
        if ($event->getCheckoutId() && $this->isAcceptableCheckoutType($event, $this->getCheckoutType())) {
            /** @var BaseCheckout $checkout */
            $checkout = $this->getRepository()->find($event->getCheckoutId());
        } elseif ($event->getSource() && $event->getSource()->getId()) {
            /** @var BaseCheckout $checkout */
            $checkout = $this->getRepository()->findOneBy(['source' => $event->getSource()]);
        }

        return isset($checkout) ? $this->actualizeCheckoutCurrency($checkout) : null;
    }

    /**
     * @param BaseCheckout $checkout
     * @return BaseCheckout
     */
    protected function actualizeCheckoutCurrency(BaseCheckout $checkout)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass('OroB2BCheckoutBundle:BaseCheckout');
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

        if (!$this->isStartWorkflowAllowed($checkout)) {
            return null;
        }

        return $checkout;
    }

    /**
     * @param $checkout
     * @return bool
     */
    protected function isStartWorkflowAllowed($checkout)
    {
        return $this->workflowManager->isStartTransitionAvailable(
            $this->getWorkflowName(),
            static::START_TRANSITION_DEFINITION,
            $checkout
        );
    }

    /**
     * @param CheckoutEntityEvent $event
     * @param string $checkoutType
     * @return bool
     */
    protected function isAcceptableCheckoutType(CheckoutEntityEvent $event, $checkoutType)
    {
        return null === $event->getType() || $checkoutType === $event->getType();
    }

    /**
     * @return string
     */
    protected function getCheckoutType()
    {
        return $this->checkoutType;
    }

    /**
     * @return string
     */
    protected function getCheckoutClassName()
    {
        return $this->checkoutClassName;
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        //todo fix in scope of issue BAP-10825
        throw new \RuntimeException(
            'TODO. Implement. getApplicableWorkflowByEntityClass was removed'
        );

        //return $this->workflowManager->getApplicableWorkflowByEntityClass($this->getCheckoutClassName());
    }

    /**
     * @return CheckoutInterface
     */
    protected function createCheckoutEntity()
    {
        $checkoutClassName = $this->getCheckoutClassName();

        return new $checkoutClassName;
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
