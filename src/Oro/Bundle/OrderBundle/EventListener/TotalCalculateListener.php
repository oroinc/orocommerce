<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

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
            if (!$this->isDefinedForm($currentApplication)
                || !$request->request->has($this->forms[$currentApplication])) {
                return;
            }

            $form = $this->formFactory->create($this->forms[$currentApplication], $entity);
            $form->handleRequest($request);
        }
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
