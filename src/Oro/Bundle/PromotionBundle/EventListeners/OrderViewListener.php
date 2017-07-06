<?php

namespace Oro\Bundle\PromotionBundle\EventListeners;

use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class OrderViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onView(BeforeListRenderEvent $event)
    {
        /** @var Order $order */
        $order = $event->getEntity();

        $template = $event->getEnvironment()->render('@OroPromotion/Order/discounts_view.html.twig', [
            'entity' => $order,
        ]);

        $blockTitle = $this->translator->trans('oro.promotion.entity_plural_label');

        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockTitle, -75);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
