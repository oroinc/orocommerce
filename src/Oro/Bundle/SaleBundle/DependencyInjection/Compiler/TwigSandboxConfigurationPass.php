<?php

namespace Oro\Bundle\SaleBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Compiler pass that allows twig functions `oro_email.email_renderer` service.
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    /**
     * {@inheritdoc}
     */
    protected function getFunctions()
    {
        return [
            'quote_guest_access_link'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            'oro_sale.twig.quote_guest_access'
        ];
    }
}
