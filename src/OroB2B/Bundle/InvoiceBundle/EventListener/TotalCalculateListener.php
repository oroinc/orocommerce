<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener;

use Symfony\Component\Form\FormFactory;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Form\Type\InvoiceType;
use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;

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
