<?php

namespace Oro\Bundle\OrderBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers Twig functions for the email templates rendering sandbox:
 *  - oro_order_shipping_method_label
 *  - oro_order_get_shipping_trackings
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'oro_order_shipping_method_label',
            'oro_order_get_shipping_trackings'
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
            'oro_order.twig.order_shipping',
            'oro_order.twig.order'
        ];
    }
}
