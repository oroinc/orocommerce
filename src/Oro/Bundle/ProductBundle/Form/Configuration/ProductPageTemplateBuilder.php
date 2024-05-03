<?php

namespace Oro\Bundle\ProductBundle\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\RadioBuilder;

/**
 * Builds page_template choice type for theme configuration
 */
class ProductPageTemplateBuilder extends RadioBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'product_page_template';
    }

    /**
     * {@inheritDoc}
     */
    #[\Override] protected function getConfiguredOptions($option): array
    {
        return array_merge(parent::getConfiguredOptions($option), [
            'choices' => array_flip($option['values']),
            'placeholder' => 'oro.product.theme.default.configuration.product_details.template.values.default',
        ]);
    }
}
