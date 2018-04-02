<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CustomerGroupFormViewListener extends AbstractFormViewListener
{
    /**
     * {@inheritdoc}
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntityFromRequest();
        if (!$customerGroup) {
            return;
        }

        $entity = $customerGroup->getTaxCode();

        $template = $event->getEnvironment()->render(
            'OroTaxBundle:CustomerGroup:tax_code_view.html.twig',
            ['entity' => $entity]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroTaxBundle:CustomerGroup:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
