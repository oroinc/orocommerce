<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add Checkout Start transition to a list of CAPTCHA protected forms.
 *
 * `oro_workflow_checkout_start` is a "fake" form name used to identify form later during checks and allow it to be
 * configured via the system configuration.
 */
class AddCheckoutStartToCaptchaProtected implements CompilerPassInterface
{
    public const PROTECTION_KEY = 'oro_workflow_checkout_start';

    public function process(ContainerBuilder $container)
    {
        $protectedFormsRegistry = $container->getDefinition('oro_form.captcha.protected_forms_registry');
        $protectedFormsRegistry->addMethodCall('protectForm', [self::PROTECTION_KEY]);
    }
}
