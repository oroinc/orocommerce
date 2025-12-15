<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;

/**
 * Provides the fields that are used for product fallback processing.
 */
class ProductFallbackFieldProvider implements ProductFallbackFieldProviderInterface
{
    private const string PAGE_TEMPLATE_FIELD = 'pageTemplate';

    #[\Override]
    public function getFieldsByFallbackId(): array
    {
        return [
            ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                self::PAGE_TEMPLATE_FIELD,
            ],
        ];
    }
}
