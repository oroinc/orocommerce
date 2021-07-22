<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the following Twig filters for the email templates rendering sandbox:
 * * oro_format_short_product_unit_value
 * * oro_format_product_unit_label
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritdoc}
     */
    protected function getFunctions(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters(): array
    {
        return [
            'oro_format_short_product_unit_value',
            'oro_format_product_unit_label'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTags(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            'oro_product.twig.product_unit_extension'
        ];
    }
}
