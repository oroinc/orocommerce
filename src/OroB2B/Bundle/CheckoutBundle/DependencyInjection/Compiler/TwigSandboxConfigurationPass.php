<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler;

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
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);

            $functions = array_merge($securityPolicyDef->getArgument(4), ['order_line_items']);
            $tags = array_merge($securityPolicyDef->getArgument(0), ['set']);
            $filters = $securityPolicyDef->getArgument(1);
            $filters = array_merge($filters, ['join']);
            $securityPolicyDef->replaceArgument(0, $tags);
            $securityPolicyDef->replaceArgument(1, $filters);
            $securityPolicyDef->replaceArgument(4, $functions);

            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall('addExtension', [new Reference('orob2b_checkout.twig.line_items')]);
        }
    }
}
