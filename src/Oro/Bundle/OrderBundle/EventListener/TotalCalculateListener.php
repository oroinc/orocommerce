<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;

class TotalCalculateListener
{
    /** @var array */
    protected $forms = [
        ActionCurrentApplicationProvider::DEFAULT_APPLICATION => OrderType::NAME
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /**
     * @param FormFactory $formFactory
     * @param CurrentApplicationProviderInterface $applicationProvider
     */
    public function __construct(FormFactory $formFactory, CurrentApplicationProviderInterface $applicationProvider)
    {
        $this->formFactory = $formFactory;
        $this->applicationProvider = $applicationProvider;
    }

    /**
     * @param TotalCalculateBeforeEvent $event
     */
    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event)
    {
        /** @var Order $entity */
        $entity = $event->getEntity();
        $request = $event->getRequest();

        if ($entity instanceof Order) {
            $currentApplication = $this->applicationProvider->getCurrentApplication();
            $entity->resetLineItems();

            if ($currentApplication === ActionCurrentApplicationProvider::DEFAULT_APPLICATION) {
                $entity->resetDiscounts();
            }

            if ($form = $this->createForm($entity, $currentApplication)) {
                $form->submit($request, false);
            }
        }
    }

    /**
     * @param object $entity
     * @param string $currentApplication - Application Name
     *
     * @return null|Form|FormInterface
     */
    protected function createForm($entity, $currentApplication)
    {
        $form = null;

        if ($this->isDefinedForm($currentApplication)) {
            $form = $this->formFactory->create($this->forms[$currentApplication], $entity);
        }

        return $form;
    }

    /**
     * @param string $currentApplication - Application Name
     *
     * @return bool
     */
    protected function isDefinedForm($currentApplication)
    {
        return array_key_exists($currentApplication, $this->forms) && $this->forms[$currentApplication];
    }
}
