<?php

namespace OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const UI_EXTENSION_SERVICE_KEY = 'oro_ui.twig.html_tag';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
        $filters = $securityPolicyDef->getArgument(1);
        $filters = array_merge(
            $filters,
            [
                'orob2b_format_short_product_unit_value'
            ]
        );
        $securityPolicyDef->replaceArgument(1, $filters);

        $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
        $rendererDef->addMethodCall('addExtension', [new Reference('orob2b_product.twig.product_unit_value')]);
    }
}
