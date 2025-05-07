<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds Checkout Start transition to a list of CAPTCHA protected forms.
 *
 * "oro_workflow_checkout_start" is a "fake" form name used to identify form later during checks and allow it to be
 * configured via the system configuration.
 */
class AddCheckoutStartToCaptchaProtectedPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_form.captcha.protected_forms_registry')
            ->addMethodCall('protectForm', ['oro_workflow_checkout_start']);
    }
}
