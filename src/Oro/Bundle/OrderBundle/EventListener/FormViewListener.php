<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Account;

class FormViewListener
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
    public function onAccountUserView(BeforeListRenderEvent $event)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntityFromRequestId('OroCustomerBundle:AccountUser');
        if ($accountUser) {
            $template = $event->getEnvironment()->render(
                'OroOrderBundle:AccountUser:orders_view.html.twig',
                ['entity' => $accountUser]
            );
            $this->addSalesOrdersBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        /** @var Account $account */
        $account = $this->getEntityFromRequestId('OroCustomerBundle:Account');
        if ($account) {
            $template = $event->getEnvironment()->render(
                'OroOrderBundle:Account:orders_view.html.twig',
                ['entity' => $account]
            );
            $this->addSalesOrdersBlock($event->getScrollData(), $template);
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
     */
    protected function addSalesOrdersBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('oro.order.sales_orders.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
