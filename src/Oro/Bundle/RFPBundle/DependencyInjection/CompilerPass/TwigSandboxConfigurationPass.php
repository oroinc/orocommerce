<?php

namespace Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "rfp_products" Twig function for the email templates rendering sandbox.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'rfp_products'
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
            'oro_rfp.twig.request_products'
        ];
    }
}
