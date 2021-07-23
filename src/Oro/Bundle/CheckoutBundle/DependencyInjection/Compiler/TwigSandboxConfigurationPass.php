<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "order_line_items" Twig function, the "join" Twig filter
 * and the "set" Twig tag for the email templates rendering sandbox.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions(): array
    {
        return [
            'order_line_items'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters(): array
    {
        return [
            'join'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTags(): array
    {
        return [
            'set'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            'oro_checkout.twig.line_items'
        ];
    }
}
