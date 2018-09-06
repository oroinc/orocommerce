<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Compiler pass that collects extensions for service `oro_payment_term.twig.payment_term_extension`
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
            'get_payment_term'
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
            'oro_payment_term.twig.payment_term_extension'
        ];
    }
}
