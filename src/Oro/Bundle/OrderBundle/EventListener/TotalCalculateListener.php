<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistryInterface;

class TotalCalculateListener
{
    /** @var array */
    protected $forms = [
        ActionCurrentApplicationProvider::DEFAULT_APPLICATION => OrderType::class
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /** @var FormRegistryInterface */
    protected $formRegistry;

    /**
     * @param FormFactory $formFactory
     * @param CurrentApplicationProviderInterface $applicationProvider
     * @param FormRegistryInterface $formRegistry
     */
    public function __construct(
        FormFactory $formFactory,
        CurrentApplicationProviderInterface $applicationProvider,
        FormRegistryInterface $formRegistry
    ) {
        $this->formFactory = $formFactory;
        $this->applicationProvider = $applicationProvider;
        $this->formRegistry = $formRegistry;
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
            if (!$this->isDefinedForm($currentApplication)) {
                return;
            }

            $formName = $this->getFormName($this->forms[$currentApplication]);
            if (!$request->request->has($formName)) {
                return;
            }

            $form = $this->formFactory->create($this->forms[$currentApplication], $entity);
            $form->submit($request);
        }
    }

    /**
     * @param string $formClass
     * @return string
     */
    private function getFormName($formClass)
    {
        $type = $this->formRegistry->getType($formClass);

        return $type->getName(); // TODO replace with getBlockPrefix in scope of BAP-15236
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
