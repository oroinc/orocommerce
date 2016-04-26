<?php

namespace OroB2B\Bundle\ShippingBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class FormViewListener
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ShippingOriginProvider */
    protected $shippingOriginProvider;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingOriginProvider $shippingOriginProvider
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        ShippingOriginProvider $shippingOriginProvider,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWarehouseView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $warehouseId = (int)$request->get('id');
        if (!$warehouseId) {
            return;
        }

        /** @var Warehouse $warehouse */
        $warehouse = $this->doctrineHelper->getEntityReference('OroB2BWarehouseBundle:Warehouse', $warehouseId);
        if (!$warehouse) {
            return;
        }

        $shippingOrigin = $this->shippingOriginProvider->getShippingOriginByWarehouse($warehouse);

        if ($shippingOrigin->isEmpty()) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroB2BShippingBundle:Warehouse:shipping_origin_view.html.twig',
            ['entity' => $shippingOrigin]
        );
        $this->addWarehouseBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWarehouseEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BShippingBundle:Warehouse:shipping_origin_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addWarehouseBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string     $html
     */
    protected function addWarehouseBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.shipping.warehouse.section.shipping_origin');
        $blockId    = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
