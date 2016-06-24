<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

abstract class AbstractAccountFormViewListener
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
     * @var string
     */
    protected $updateTemplate = 'OroB2BPricingBundle:Account:price_list_update.html.twig';

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            $this->updateTemplate,
            ['form' => $event->getFormView()]
        );
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param string $updateTemplate
     */
    public function setUpdateTemplate($updateTemplate)
    {
        $this->updateTemplate = $updateTemplate;
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param BasePriceListRelation[] $priceLists
     * @param string $fallback
     */
    protected function addPriceListInfo(
        BeforeListRenderEvent $event,
        array $priceLists,
        $fallback
    ) {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Account:price_list_view.html.twig',
            [
                'priceLists' => $priceLists,
                'fallback' => $fallback
            ]
        );

        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
