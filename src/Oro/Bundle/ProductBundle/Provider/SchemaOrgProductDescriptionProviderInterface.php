<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for the provider of a description that will be used in the Schema.org "description" property.
 */
interface SchemaOrgProductDescriptionProviderInterface
{
    /**
     * Provides description suitable for using in the Schema.org "description" property.
     * Must not contain any HTML tags.
     *
     * @param Product $product
     * @param Localization|null $localization
     * @param object|null $scopeIdentifier Scope to pass to {@see \Oro\Bundle\ConfigBundle\Config\ConfigManager::get}.
     *
     * @return string
     */
    public function getDescription(
        Product $product,
        ?Localization $localization = null,
        ?object $scopeIdentifier = null
    ): string;
}
