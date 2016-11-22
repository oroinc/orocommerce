<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;

class AccountGroupFormViewListener extends AbstractFormViewListener
{
    /**
     * {@inheritdoc}
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntityFromRequest();
        if (!$accountGroup) {
            return;
        }

        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
        $entity = $repository->findOneByAccountGroup($accountGroup);

        $template = $event->getEnvironment()->render(
            'OroTaxBundle:AccountGroup:tax_code_view.html.twig',
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
            'OroTaxBundle:AccountGroup:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
