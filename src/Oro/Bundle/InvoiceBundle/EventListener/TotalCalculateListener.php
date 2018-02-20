<?php

namespace Oro\Bundle\InvoiceBundle\EventListener;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Form\Type\InvoiceType;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Symfony\Component\Form\FormFactory;

class TotalCalculateListener
{
    /** @var FormFactory */
    protected $formFactory;

    /**
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param TotalCalculateBeforeEvent $args
     */
    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $args)
    {
        /** @var Invoice $entity */
        $entity = $args->getEntity();
        $request = $args->getRequest();

        if ($entity instanceof Invoice) {
            $form = $this->formFactory->create(InvoiceType::NAME, $entity);
            $form->submit($request, false);
        }
    }
}
