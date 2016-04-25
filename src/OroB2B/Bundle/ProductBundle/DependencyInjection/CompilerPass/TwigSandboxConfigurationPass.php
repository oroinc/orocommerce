<?php

namespace OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY) &&
            $container->has(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
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
}
