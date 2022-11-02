<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistryInterface;

/**
 * Recalculates order totals based of request parameters.
 */
class TotalCalculateListener
{
    /** @var FormFactory */
    private $formFactory;

    /** @var FormRegistryInterface */
    private $formRegistry;

    /** @var FrontendHelper */
    private $frontendHelper;

    public function __construct(
        FormFactory $formFactory,
        FormRegistryInterface $formRegistry,
        FrontendHelper $frontendHelper
    ) {
        $this->formFactory = $formFactory;
        $this->formRegistry = $formRegistry;
        $this->frontendHelper = $frontendHelper;
    }

    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event)
    {
        $entity = $event->getEntity();
        $request = $event->getRequest();

        if ($entity instanceof Order
            && !$this->frontendHelper->isFrontendRequest()
            && $request->request->has($this->getFormName(OrderType::class))
        ) {
            $form = $this->formFactory->create(OrderType::class, $entity);
            $form->submit($request->get($form->getName()));
        }
    }

    private function getFormName(string $type): string
    {
        return $this->formRegistry->getType($type)->getBlockPrefix();
    }
}
