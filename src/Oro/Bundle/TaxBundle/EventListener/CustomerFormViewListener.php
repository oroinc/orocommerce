<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CustomerFormViewListener extends AbstractFormViewListener
{
    /**
     * {@inheritdoc}
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var Customer $customer */
        $customer = $this->getEntityFromRequest();
        if (!$customer) {
            return;
        }

        $entity = $customer->getTaxCode();

        $groupCustomerTaxCode = null;
        if (!$entity && $customer->getGroup()) {
            $groupCustomerTaxCode = $customer->getGroup()->getTaxCode();
        }

        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Customer:tax_code_view.html.twig',
            ['entity' => $entity, 'groupCustomerTaxCode' => $groupCustomerTaxCode]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Customer:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
