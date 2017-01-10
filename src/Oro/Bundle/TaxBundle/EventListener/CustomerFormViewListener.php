<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;

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

        /** @var CustomerTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
        $entity = $repository->findOneByCustomer($customer);

        $groupCustomerTaxCode = null;
        if (!$entity && $customer->getGroup()) {
            $groupCustomerTaxCode = $repository->findOneByCustomerGroup($customer->getGroup());
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
