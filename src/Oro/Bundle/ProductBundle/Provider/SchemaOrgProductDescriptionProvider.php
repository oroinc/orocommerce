<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides the product description from the inner providers taking into account current system configuration.
 */
class SchemaOrgProductDescriptionProvider implements SchemaOrgProductDescriptionProviderInterface
{
    private ConfigManager $configManager;

    /**
     * @var array<string, SchemaOrgProductDescriptionProviderInterface>
     */
    private array $productDescriptionProviders;

    /**
     * @param ConfigManager $configManager
     * @param iterable<string, SchemaOrgProductDescriptionProviderInterface> $productDescriptionProviders
     */
    public function __construct(
        ConfigManager $configManager,
        iterable $productDescriptionProviders
    ) {
        $this->configManager = $configManager;

        if ($productDescriptionProviders instanceof \Traversable) {
            $productDescriptionProviders = iterator_to_array($productDescriptionProviders);
        }

        $this->productDescriptionProviders = $productDescriptionProviders;
    }

    public function getDescription(
        Product $product,
        ?Localization $localization = null,
        ?object $scopeIdentifier = null
    ): string {
        $option = $this->getSchemaOrgSettings($scopeIdentifier);

        return $this->productDescriptionProviders[$option]->getDescription($product, $localization, $scopeIdentifier);
    }

    private function getSchemaOrgSettings(?object $scopeIdentifier): string
    {
        return (string) $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::SCHEMA_ORG_DESCRIPTION_FIELD),
            false,
            false,
            $scopeIdentifier
        );
    }
}
