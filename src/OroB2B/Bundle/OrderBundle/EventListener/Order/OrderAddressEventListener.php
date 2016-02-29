<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use Symfony\Component\Form\FormView;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;

class OrderAddressEventListener
{
    /** @var EngineInterface */
    protected $engine;

    /**
     * @param EngineInterface $engine
     */
    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $orderForm = $event->getForm();

        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            $fieldName = sprintf('%sAddress', $type);
            if ($orderForm->has($fieldName)) {
                $view = $this->renderForm($orderForm->get($fieldName)->createView());
                $event->getData()->offsetSet($fieldName, $view);
            }
        }
    }

    /**
     * @param FormView $formView
     *
     * @return string
     */
    protected function renderForm(FormView $formView)
    {
        return $this->engine->render('OroB2BOrderBundle:Form:accountAddressSelector.html.twig', ['form' => $formView]);
    }
}
