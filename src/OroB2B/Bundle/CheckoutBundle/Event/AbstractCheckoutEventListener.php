<?php

namespace OroB2B\Bundle\CheckoutBundle\Event;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;

abstract class AbstractCheckoutEventListener
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
     * @param CheckoutEvent $event
     * @return AlternativeCheckout
     */
    public function onGetCheckoutEntity(CheckoutEvent $event)
    {
        $checkout = $this->createCheckoutEntity();

        if (!$this->isStartWorkflowAllowed($checkout)) {
            return;
        }

        $className = get_class($checkout);
        $repository = $this->doctrine->getManagerForClass($className)
            ->getRepository($className);

        if ($this->isSupportedCheckout($event, $checkout)) {
            $checkout = $repository->find($event->getCheckoutId());
        } elseif ($event->getSource()) {
            $checkout = $repository->findOneBy([
                'source' => $event->getSource()
            ]);
        }

        if ($checkout) {
            $event->setCheckoutEntity($checkout);
        }
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
     * @param CheckoutEvent $event
     * @param CheckoutInterface $checkout
     * @return bool
     */
    protected function isSupportedCheckout(CheckoutEvent $event, CheckoutInterface $checkout)
    {
        return $checkout->getType() === $event->getType() && $event->getCheckoutId();
    }

    /**
     * @return string
     */
    abstract protected function getWorkflowName();

    /**
     * @return CheckoutInterface|
     */
    abstract protected function createCheckoutEntity();
}
