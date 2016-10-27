<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;

class AccountFormViewListener extends AbstractFormViewListener
{
    /**
     * {@inheritdoc}
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var Account $account */
        $account = $this->getEntityFromRequest();
        if (!$account) {
            return;
        }

        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->taxCodeClass);
        $entity = $repository->findOneByAccount($account);

        $groupAccountTaxCode = null;
        if (!$entity && $account->getGroup()) {
            $groupAccountTaxCode = $repository->findOneByAccountGroup($account->getGroup());
        }

        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Account:tax_code_view.html.twig',
            ['entity' => $entity, 'groupAccountTaxCode' => $groupAccountTaxCode]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroTaxBundle:Account:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
