<?php

namespace OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';
    const WEBSITE_PATH_EXTENSION_SERVICE_KEY = 'orob2b_website.twig.website_path';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY) &&
            $container->has(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
            // register 'website_path' and 'website_secure_path' functions
            $securityPolicyDef = $container->getDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);
            $functions = $securityPolicyDef->getArgument(4);
            $functions = array_merge(
                $functions,
                [
                    'website_path',
                    'website_secure_path'
                ]
            );
            $securityPolicyDef->replaceArgument(4, $functions);

            // register an twig extension implements this function
            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall('addExtension', [new Reference(self::WEBSITE_PATH_EXTENSION_SERVICE_KEY)]);
        }
    }
}
