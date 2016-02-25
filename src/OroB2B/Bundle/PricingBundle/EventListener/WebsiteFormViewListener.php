<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Collections\Criteria;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteFormViewListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $websiteClassName;

    /** @var  string */
    protected $priceListToWebsiteClassName;

    /** @var  TranslatorInterface */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     * @param string $websiteClassName
     * @param string $priceListToWebsiteClassName
     */
    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator,
        $websiteClassName,
        $priceListToWebsiteClassName
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteClassName = $websiteClassName;
        $this->priceListToWebsiteClassName = $priceListToWebsiteClassName;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWebsiteEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Website:price_list_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addPriceListsBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWebsiteView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }
        $id = (int)$request->get('id');

        /** @var Website $product */
        $website = $this->doctrineHelper->getEntityReference($this->websiteClassName, $id);
        $priceLists = $this->doctrineHelper
            ->getEntityManager($this->priceListToWebsiteClassName)
            ->getRepository($this->priceListToWebsiteClassName)
            ->findBy(['website' => $website], ['priority' => Criteria::DESC]);
        /** @var  PriceListWebsiteFallback $fallbackEntity */
        $fallbackEntity = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->findOneBy(['website' => $website]);
        $choices = [
            PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY =>
                'orob2b.pricing.fallback.current_website_only.label',
            PriceListWebsiteFallback::CONFIG =>
                'orob2b.pricing.fallback.config.label',
        ];
        $fallback = $fallbackEntity ? $choices[$fallbackEntity->getFallback()]
            : $choices[PriceListWebsiteFallback::CONFIG];

        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:PriceList/partial:list.html.twig',
            [
                'entities' => $priceLists,
                'website' => $website,
                'fallback' => $fallback,
            ]
        );
        $this->addPriceListsBlock($event->getScrollData(), $template);
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addPriceListsBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
