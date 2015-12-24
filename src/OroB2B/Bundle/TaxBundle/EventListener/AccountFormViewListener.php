<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountGroupTaxCodeRepository;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;

class AccountFormViewListener extends AbstractFormViewListener
{
    /** @var string */
    protected $accountGroupTaxCodeClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack   $requestStack
     * @param string         $taxCodeClass
     * @param string         $accountGroupTaxCodeClass
     * @param string         $entityClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack,
        $taxCodeClass,
        $entityClass,
        $accountGroupTaxCodeClass
    ) {
        parent::__construct(
            $doctrineHelper,
            $requestStack,
            $taxCodeClass,
            $entityClass
        );
        $this->accountGroupTaxCodeClass = $accountGroupTaxCodeClass;
    }

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

        $accountGroupTaxCode = null;
        if (!$entity && $account->getGroup()) {
            /** @var AccountGroupTaxCodeRepository $accountGroupTaxCodeRepository */
            $accountGroupTaxCodeRepository = $this->doctrineHelper->getEntityRepository(
                $this->accountGroupTaxCodeClass
            );

            $accountGroupTaxCode = $accountGroupTaxCodeRepository->findOneByAccountGroup($account->getGroup());
        }

        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Account:tax_code_view.html.twig',
            ['entity' => $entity, 'accountGroupTaxCode' => $accountGroupTaxCode]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle:Account:tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
