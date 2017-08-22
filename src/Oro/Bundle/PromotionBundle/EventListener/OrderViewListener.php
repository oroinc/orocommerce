<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * This listener adds promotions table on order view and order edit pages.
 */
class OrderViewListener
{
    const DISCOUNTS_BLOCK_ID = 'discounts';

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
     * @throws \LogicException
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        if (!$event->getScrollData()->hasBlock(self::DISCOUNTS_BLOCK_ID)) {
            throw new \LogicException(sprintf('Scroll data must contain block with id "%s"', self::DISCOUNTS_BLOCK_ID));
        }

        $template = $event->getEnvironment()->render(
            'OroPromotionBundle:Order:promotions_collection.html.twig',
            ['form' => $event->getFormView()]
        );

        $this->addPromotionsSubBlock($event, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $template
     */
    protected function addPromotionsSubBlock(BeforeListRenderEvent $event, string $template)
    {
        $scrollData = $event->getScrollData();
        $blockTitle = $this->translator->trans('oro.promotion.sections.promotion_and_discounts.label');
        $scrollData->changeBlock(self::DISCOUNTS_BLOCK_ID, $blockTitle);
        $subBlockId = $scrollData->addSubBlock(self::DISCOUNTS_BLOCK_ID);
        $scrollData->addSubBlockData(self::DISCOUNTS_BLOCK_ID, $subBlockId, $template);
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
