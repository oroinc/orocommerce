<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Compiler pass that collects extensions for service `oro_promotion.twig.extension.discounts_information`
 * by `oro_email.email_renderer` tag
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritDoc}
     */
    protected function getFunctions()
    {
        return [
            'line_items_discounts'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilters()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        return [
            'oro_promotion.twig.extension.discounts_information'
        ];
    }
}
