<?php

namespace Oro\Bundle\PromotionBundle\EventListeners;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\Translation\TranslatorInterface;

class OrderViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onView(BeforeListRenderEvent $event)
    {
        /** @var Order $order */
        $order = $event->getEntity();

        $template = $event->getEnvironment()->render('@OroPromotion/Order/discounts_view.html.twig', [
            'entity' => $order
        ]);

        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($this->translator->trans('oro.promotion.entity_plural_label'), -75);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
