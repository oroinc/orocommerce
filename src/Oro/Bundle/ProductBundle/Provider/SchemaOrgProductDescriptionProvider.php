<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Container\ContainerInterface;

/**
 * Provides the product description from the inner providers taking into account current system configuration.
 */
class SchemaOrgProductDescriptionProvider implements SchemaOrgProductDescriptionProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $productDescriptionProviders,
        private readonly ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function getDescription(
        Product $product,
        ?Localization $localization = null,
        ?object $scopeIdentifier = null
    ): string {
        return $this->getProductDescriptionProviders($this->getSchemaOrgSettings($scopeIdentifier))
            ->getDescription($product, $localization, $scopeIdentifier);
    }

    private function getSchemaOrgSettings(?object $scopeIdentifier): string
    {
        return (string)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::SCHEMA_ORG_DESCRIPTION_FIELD),
            false,
            false,
            $scopeIdentifier
        );
    }

    private function getProductDescriptionProviders(string $option): SchemaOrgProductDescriptionProviderInterface
    {
        return $this->productDescriptionProviders->get($option);
    }
}
