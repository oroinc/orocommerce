<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerViewListener implements FeatureToggleableInterface
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
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var Customer $customer */
        $customer = $this->getEntityFromRequestId('OroCustomerBundle:Customer');
        if ($customer) {
            $template = $event->getEnvironment()->render(
                'OroRFPBundle:Customer:rfp_view.html.twig',
                ['entity' => $customer]
            );

            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('oro.rfp.datagrid.customer.label')
            );
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntityFromRequestId('OroCustomerBundle:CustomerUser');
        if ($customerUser) {
            $template = $event->getEnvironment()->render(
                'OroRFPBundle:CustomerUser:rfp_view.html.twig',
                ['entity' => $customerUser]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans('oro.rfp.datagrid.customer_user.label')
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
