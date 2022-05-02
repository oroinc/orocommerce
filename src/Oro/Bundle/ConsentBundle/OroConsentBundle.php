<?php

namespace Oro\Bundle\ConsentBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroConsentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\ConsentBundle\Entity\Consent' => [
                'name' => 'names'
            ]
        ]));
    }
}
