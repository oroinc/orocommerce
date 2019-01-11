<?php

namespace Oro\Bundle\ConsentBundle;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Allows to manage user consents, for example consent for processing of personal data
 * or consent for receiving emails from the service.
 */
class OroConsentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            Consent::class => [
                'name' => 'names'
            ]
        ]));
    }
}
