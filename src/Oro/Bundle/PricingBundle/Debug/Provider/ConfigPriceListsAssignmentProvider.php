<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about price lists assigned to config level.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class ConfigPriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    public function __construct(
        private ConfigManager $configManager,
        private PriceListConfigConverter $configConverter,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getPriceListAssignments(): ?array
    {
        $priceLists = $this->configConverter->convertFromSaved(
            $this->configManager->get('oro_pricing.default_price_lists')
        );

        return [
            'section_title' => $this->translator->trans('oro.config.menu.system_configuration.label'),
            'link' => $this->urlGenerator->generate(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'pricing']
            ),
            'link_title' => $this->translator->trans('oro.config.module_label'),
            'fallback' => null,
            'fallback_entity_title' => null,
            'price_lists' => $priceLists,
            'stop' => false
        ];
    }
}
