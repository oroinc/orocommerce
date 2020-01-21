<?php

namespace Oro\Bundle\ConsentBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ConsentBundle bundle class.
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
            'Oro\Bundle\ConsentBundle\Entity\Consent' => [
                'name' => 'names'
            ]
        ]));
    }
}
