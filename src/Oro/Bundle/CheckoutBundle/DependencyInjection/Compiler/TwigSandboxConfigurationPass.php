<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "order_line_items" Twig function, the "join" Twig filter
 * and the "set" Twig tag for the email templates rendering sandbox.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'order_line_items'
        ];
    }

    #[\Override]
    protected function getFilters(): array
    {
        return [
            'join'
        ];
    }

    #[\Override]
    protected function getTags(): array
    {
        return [
            'set'
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            'oro_checkout.twig.line_items'
        ];
    }
}
