<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;

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
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntityFromRequestId('OroCustomerBundle:CustomerUser');
        if ($customerUser) {
            $template = $event->getEnvironment()->render(
                'OroOrderBundle:CustomerUser:orders_view.html.twig',
                ['entity' => $customerUser]
            );
            $this->addSalesOrdersBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerView(BeforeListRenderEvent $event)
    {
        /** @var Customer $customer */
        $customer = $this->getEntityFromRequestId('OroCustomerBundle:Customer');
        if ($customer) {
            $template = $event->getEnvironment()->render(
                'OroOrderBundle:Customer:orders_view.html.twig',
                ['entity' => $customer]
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
