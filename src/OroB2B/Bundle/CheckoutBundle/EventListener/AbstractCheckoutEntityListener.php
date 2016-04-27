<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;

/**
 * While implementing custom checkout, alternative checkout entity can be set
 * This Event Listener can be used as base for replacement
 */
abstract class AbstractCheckoutEntityListener
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
     * @param WorkflowManager $workflowManager
     * @param RegistryInterface $doctrine
     */
    public function __construct(WorkflowManager $workflowManager, RegistryInterface $doctrine)
    {
        $this->workflowManager = $workflowManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @param CheckoutEntityEvent $event
     * @return AlternativeCheckout
     */
    public function onGetCheckoutEntity(CheckoutEntityEvent $event)
    {
        $checkout = $this->createCheckoutEntity();

        if ($this->isNotAcceptableCheckoutType($event, $checkout) || !$this->isStartWorkflowAllowed($checkout)) {
            return;
        }

        $className = get_class($checkout);
        $repository = $this->doctrine->getManagerForClass($className)
            ->getRepository($className);

        if ($event->getCheckoutId()) {
            /** @var CheckoutInterface $checkout */
            $checkout = $repository->find($event->getCheckoutId());
            $event->setCheckoutEntity($checkout);
            return;
        }

        if ($event->getSource()) {
            $checkout = $repository->findOneBy([
                'source' => $event->getSource()
            ]) ?: $checkout;
            $checkout->setSource($event->getSource());
        }

        $event->setCheckoutEntity($checkout);
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
     * @param CheckoutInterface $checkout
     * @return bool
     */
    protected function isNotAcceptableCheckoutType(CheckoutEntityEvent $event, $checkout)
    {
        return !is_null($event->getType()) && $checkout->getCheckoutType() !== $event->getType();
    }

    /**
     * @return string
     */
    abstract protected function getWorkflowName();

    /**
     * @return CheckoutInterface
     */
    abstract protected function createCheckoutEntity();
}
