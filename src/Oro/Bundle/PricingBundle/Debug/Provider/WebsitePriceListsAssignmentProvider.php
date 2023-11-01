<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about price lists assigned to website.
 */
class WebsitePriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ManagerRegistry $registry,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator
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

        return [
            'section_title' => $this->translator->trans('oro.website.entity_label'),
            'link' => $this->urlGenerator->generate('oro_multiwebsite_view', ['id' => $website->getId()]),
            'link_title' => $website->getName(),
            'fallback' => $fallback,
            'priceLists' => $priceLists,
            'stop' => $fallbackEntity?->getFallback() === PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY
        ];
    }
}
