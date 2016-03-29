<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvent;

class CheckoutEntityListener
{
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

        $className = get_class($checkout);
        $repository = $this->doctrine->getEntityManagerForClass($className)
            ->getRepository($className);

        if ($checkout->getType() === $event->getType() && $event->getCheckoutId()) {
            $checkout = $repository->find($event->getCheckoutId());
        } elseif ($event->getSource()) {
            $checkout = $repository->findOneBy([
                'source' => $event->getSource()
            ]);
        }

        $event->setCheckoutEntity($checkout);
    }

    /**
     * @return AlternativeCheckout
     */
    protected function createCheckoutEntity()
    {
        $checkout = new AlternativeCheckout();
        $checkout->setAllowed(false);//todo

        return $checkout;
    }
}
