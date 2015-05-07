<?php

namespace Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const APPLICATION_URL_EXTENSION = 'oro_application.twig.application_url_extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
            && $container->hasDefinition(self::APPLICATION_URL_EXTENSION)) {
            // register 'application_url' function
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
            $functions = $securityPolicyDef->getArgument(4);
            $functions = array_merge($functions, array('application_url'));
            $securityPolicyDef->replaceArgument(4, $functions);
            // register an twig extension implements this function
            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall('addExtension', array(new Reference(self::APPLICATION_URL_EXTENSION)));
        }
    }
}
