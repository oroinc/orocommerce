<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerViewListener
{
    const CUSTOMER_VIEW_TEMPLATE = 'OroSaleBundle:Customer:quote_view.html.twig';
    const CUSTOMER_LABEL = 'oro.sale.quote.datagrid.customer.label';

    const CUSTOMER_USER_VIEW_TEMPLATE = 'OroSaleBundle:CustomerUser:quote_view.html.twig';
    const CUSTOMER_USER_LABEL = 'oro.sale.quote.datagrid.customer_user.label';

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
        /** @var Customer $customer */
        $customer = $this->getEntityFromRequestId('OroCustomerBundle:Customer');
        if ($customer) {
            $template = $event->getEnvironment()->render(
                static::CUSTOMER_VIEW_TEMPLATE,
                ['entity' => $customer]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans(static::CUSTOMER_LABEL)
            );
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        /** @var CustomerUser $customer */
        $customer = $this->getEntityFromRequestId('OroCustomerBundle:CustomerUser');
        if ($customer) {
            $template = $event->getEnvironment()->render(
                static::CUSTOMER_USER_VIEW_TEMPLATE,
                ['entity' => $customer]
            );
            $this->addRequestForQuotesBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans(static::CUSTOMER_USER_LABEL)
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
