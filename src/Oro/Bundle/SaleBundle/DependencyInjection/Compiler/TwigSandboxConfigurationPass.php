<?php

namespace Oro\Bundle\SaleBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "quote_guest_access_link" Twig function for the email templates rendering sandbox:
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritdoc}
     */
    protected function getFunctions(): array
    {
        return [
            'quote_guest_access_link'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters(): array
    {
        return [];
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
            'oro_sale.twig.quote'
        ];
    }
}
