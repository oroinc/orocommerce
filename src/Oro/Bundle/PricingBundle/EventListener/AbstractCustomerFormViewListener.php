<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds scroll blocks with price list data on edit page
 * Adds method to add price list data on view
 */
abstract class AbstractCustomerFormViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

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
    protected $updateTemplate = '@OroPricing/Customer/price_list_update.html.twig';

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

    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

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
            '@OroPricing/Customer/price_list_view.html.twig',
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
