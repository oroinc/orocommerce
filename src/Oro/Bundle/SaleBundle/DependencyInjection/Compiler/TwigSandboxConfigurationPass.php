<?php

namespace Oro\Bundle\SaleBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the following Twig functions for the email templates rendering sandbox:
 *  * quote_guest_access_link
 *  * quote_products
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'quote_guest_access_link',
            'quote_products'
        ];
    }

    #[\Override]
    protected function getFilters(): array
    {
        return [];
    }

    #[\Override]
    protected function getTags(): array
    {
        return [];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            'oro_sale.twig.quote',
            'oro_sale.twig.quote_products'
        ];
    }
}
