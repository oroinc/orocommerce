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
        $this->addPromotionsBlock($event, -75);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        $this->addPromotionsBlock($event, 890);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param int $priority
     */
    protected function addPromotionsBlock(BeforeListRenderEvent $event, int $priority)
    {
        /** @var Order $order */
        $order = $event->getEntity();

        $template = $event->getEnvironment()->render('@OroPromotion/Order/discounts_promotions_block.html.twig', [
            'entity' => $order,
        ]);

        $blockTitle = $this->translator->trans('oro.promotion.entity_plural_label');

        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockTitle, $priority);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
