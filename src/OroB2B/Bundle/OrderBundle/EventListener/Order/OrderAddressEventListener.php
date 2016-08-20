<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

class OrderAddressEventListener
{
    /** @var EngineInterface */
    protected $engine;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param EngineInterface $engine
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(EngineInterface $engine, FormFactoryInterface $formFactory)
    {
        $this->engine = $engine;
        $this->formFactory = $formFactory;
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
                $orderFormName = $orderForm->getName();
                $field = $orderForm->get($fieldName);

                $form = $this->formFactory
                    ->createNamedBuilder($orderFormName)
                    ->add(
                        $fieldName,
                        $field->getConfig()->getType()->getName(),
                        $field->getConfig()->getOptions()
                    )
                    ->getForm();

                $form->submit($event->getSubmittedData());

                $view = $this->renderForm($form->get($fieldName)->createView());
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
        return $this->engine->render('OroOrderBundle:Form:accountAddressSelector.html.twig', ['form' => $formView]);
    }
}
