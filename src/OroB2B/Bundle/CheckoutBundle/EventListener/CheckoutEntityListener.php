<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;

class CheckoutEntityListener extends AbstractCheckoutEntityListener
{
    /**
     * @var string
     */
    protected $checkoutClassName = 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout';

    /**
     * {@inheritdoc}
     */
    public function onGetCheckoutEntity(CheckoutEntityEvent $event)
    {
        // If base checkout entity already exists for source we should return it
        if ($event->getSource() && $event->getSource()->getId()) {
            $checkout = $this->doctrine
                ->getManagerForClass('OroB2BCheckoutBundle:BaseCheckout')
                ->getRepository('OroB2BCheckoutBundle:BaseCheckout')
                ->findOneBy(['source' => $event->getSource()]);
            if ($checkout) {
                $event->setCheckoutEntity($checkout);
                return;
            }
        }

        $this->addCheckoutToEvent($event);
    }

    /**
     * @param string $checkoutClassName
     */
    public function setCheckoutClassName($checkoutClassName)
    {
        $this->checkoutClassName = $checkoutClassName;
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_checkout';
    }

    /**
     * @return CheckoutInterface
     */
    protected function createCheckoutEntity()
    {
        return new $this->checkoutClassName();
    }
}
