<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormView;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;

class OrderAddressEventListener
{
    /** @var EngineInterface */
    protected $engine;

    /** @var FormFactory */
    protected $factory;

    /**
     * @param EngineInterface $engine
     * @param FormFactory $factory
     */
    public function __construct(EngineInterface $engine, FormFactory $factory)
    {
        $this->engine = $engine;
        $this->factory = $factory;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $orderForm = $event->getForm();
        $newForm = $this->factory->createNamed(
            $orderForm->getName(),
            $orderForm->getConfig()->getType(),
            $orderForm->getData(),
            $orderForm->getConfig()->getOptions()
        );

        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            $fieldName = sprintf('%sAddress', $type);
            if ($newForm->has($fieldName)) {
                $field = $newForm->get($fieldName);
                $view = $this->renderForm($field->createView());
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
