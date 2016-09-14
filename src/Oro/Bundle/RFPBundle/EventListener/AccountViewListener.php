<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class AccountViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

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
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var Account $account */
        $account = $this->getEntityFromRequestId('OroAccountBundle:Account');
        if ($account) {
            $template = $event->getEnvironment()->render(
                'OroRFPBundle:Account:rfp_view.html.twig',
                ['entity' => $account]
            );

            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('oro.rfp.datagrid.account.label')
            );
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountUserView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntityFromRequestId('OroAccountBundle:AccountUser');
        if ($accountUser) {
            $template = $event->getEnvironment()->render(
                'OroRFPBundle:AccountUser:rfp_view.html.twig',
                ['entity' => $accountUser]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('oro.rfp.datagrid.account_user.label')
            );
        }
    }

    /**
     * @param string $className
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
