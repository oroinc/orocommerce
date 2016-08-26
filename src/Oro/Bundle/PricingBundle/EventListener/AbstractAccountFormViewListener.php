<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

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
     * @var WebsiteProviderInterface
     */
    protected $websiteProvider;

    /**
     * @var string
     */
    protected $updateTemplate = 'OroPricingBundle:Account:price_list_update.html.twig';

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteProviderInterface $websiteProvider
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteProvider = $websiteProvider;
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
        $blockLabel = $this->translator->trans('oro.pricing.pricelist.entity_plural_label');
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
            'OroPricingBundle:Account:price_list_view.html.twig',
            [
                'priceLists' => $priceLists,
                'fallback' => $fallback
            ]
        );

        $blockLabel = $this->translator->trans('oro.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
