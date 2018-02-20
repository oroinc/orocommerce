<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\Translation\TranslatorInterface;

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
        if (!$this->isApplicable($event)) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroPromotionBundle:AppliedPromotion:applied_promotions_view_table.html.twig',
            ['entity' => $event->getEntity()]
        );

        $this->addPromotionsSubBlock($event, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEdit(BeforeListRenderEvent $event)
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroPromotionBundle:Order:applied_promotions_and_coupons.html.twig',
            ['form' => $event->getFormView()]
        );

        $this->addPromotionsSubBlock($event, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @return bool
     */
    private function isApplicable(BeforeListRenderEvent $event): bool
    {
        return $event->getScrollData()->hasBlock(self::DISCOUNTS_BLOCK_ID);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $template
     */
    private function addPromotionsSubBlock(BeforeListRenderEvent $event, string $template)
    {
        $scrollData = $event->getScrollData();
        $blockTitle = $this->translator->trans('oro.promotion.sections.promotion_and_discounts.label');
        $scrollData->changeBlock(self::DISCOUNTS_BLOCK_ID, $blockTitle);
        $subBlockId = $scrollData->addSubBlockAsFirst(self::DISCOUNTS_BLOCK_ID);
        $scrollData->addSubBlockData(self::DISCOUNTS_BLOCK_ID, $subBlockId, $template);
    }
}
