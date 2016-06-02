<?php

namespace OroB2B\Bundle\SaleBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        /** @var Account $account */
        $account = $this->getEntityFromRequestId('OroB2BAccountBundle:Account');
        if ($account) {
            $template = $event->getEnvironment()->render(
                'OroB2BSaleBundle:Account:quote_view.html.twig',
                ['entity' => $account]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('orob2b.sale.quote.datagrid.account.label')
            );
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountUserView(BeforeListRenderEvent $event)
    {
        /** @var AccountUser $account */
        $account = $this->getEntityFromRequestId('OroB2BAccountBundle:AccountUser');
        if ($account) {
            $template = $event->getEnvironment()->render(
                'OroB2BSaleBundle:AccountUser:quote_view.html.twig',
                ['entity' => $account]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('orob2b.sale.quote.datagrid.account_user.label')
            );
        }
    }

    /**
     * @param $className
     * @return null|object
     */
    protected function getEntityFromRequestId($className)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }
        $entityId = (int)$request->get('id');
        if (!$entityId) {
            return null;
        }
        $entity = $this->doctrineHelper->getEntityReference($className, $entityId);
        if (!$entity) {
            return null;
        }
        return $entity;
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     * @param string $blockLabel
     */
    protected function addRequestForQuotesBlock(ScrollData $scrollData, $html, $blockLabel)
    {
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
