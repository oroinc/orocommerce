<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the "line_items_discounts" Twig function for the email templates rendering sandbox:
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'line_items_discounts'
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
            'oro_promotion.twig.promotion_extension'
        ];
    }
}
