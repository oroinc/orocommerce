<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

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
        $template = $event->getEnvironment()->render(
            'OroPromotionBundle:Order:discounts_promotions.html.twig',
            ['entity' => $order]
        );
        $this->addPromotionsBlock($event, $template, -75);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        /** @var Order $order */
        $order = $event->getEntity();
        $template = $event->getEnvironment()->render(
            'OroPromotionBundle:Order:discounts_promotions_with_warning.html.twig',
            ['entity' => $order]
        );
        $this->addPromotionsBlock($event, $template, 890);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $template
     * @param int $priority
     */
    protected function addPromotionsBlock(BeforeListRenderEvent $event, string $template, int $priority)
    {
        $blockTitle = $this->translator->trans('oro.promotion.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockTitle, $priority);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
