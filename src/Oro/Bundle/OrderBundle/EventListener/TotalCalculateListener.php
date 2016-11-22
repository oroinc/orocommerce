<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

class TotalCalculateListener
{
    /** @var array */
    protected $forms = [
        ActionApplicationsHelper::COMMERCE_APPLICATION => FrontendOrderType::NAME,
        ApplicationsHelper::DEFAULT_APPLICATION => OrderType::NAME
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var ActionApplicationsHelper */
    protected $applicationsHelper;

    /**
     * @param FormFactory $formFactory
     * @param ApplicationsHelperInterface $applicationsHelper
     */
    public function __construct(FormFactory $formFactory, ApplicationsHelperInterface $applicationsHelper)
    {
        $this->formFactory = $formFactory;
        $this->applicationsHelper = $applicationsHelper;
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
            $currentApplication = $this->applicationsHelper->getCurrentApplication();
            $entity->resetLineItems();

            if ($currentApplication === ApplicationsHelper::DEFAULT_APPLICATION) {
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
