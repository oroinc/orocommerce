<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about price lists assigned to website.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class WebsitePriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ManagerRegistry $registry,
        private TranslatorInterface $translator
    ) {
    }

    public function getPriceListAssignments(): ?array
    {
        $website = $this->requestHandler->getWebsite();
        if (!$website) {
            return null;
        }

        $priceLists = $this->registry->getRepository(PriceListToWebsite::class)
            ->findBy(
                ['website' => $website],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            );

        /** @var PriceListWebsiteFallback $fallbackEntity */
        $fallbackEntity = $this->registry->getRepository(PriceListWebsiteFallback::class)
            ->findOneBy(['website' => $website]);

        $fallbackChoices = [
            PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY => 'oro.pricing.fallback.current_website_only.label',
            PriceListWebsiteFallback::CONFIG => 'oro.pricing.fallback.config.label',
        ];
        $fallback = $fallbackEntity
            ? $fallbackChoices[$fallbackEntity->getFallback()]
            : $fallbackChoices[PriceListWebsiteFallback::CONFIG];

        $isCurrentOnly = $fallbackEntity?->getFallback() === PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY;

        return [
            'section_title' => $this->translator->trans('oro.website.entity_label'),
            'link' => null,
            'link_title' => $website->getName(),
            'fallback' => $fallback,
            'fallback_entity_title' => $isCurrentOnly ? null : $this->translator->trans('oro.config.module_label'),
            'price_lists' => $priceLists,
            'stop' => $isCurrentOnly
        ];
    }
}
