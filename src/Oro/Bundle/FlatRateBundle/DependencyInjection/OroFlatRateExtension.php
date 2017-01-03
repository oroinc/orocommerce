<?php

namespace Oro\Bundle\FlatRateBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFlatRateExtension extends Extension
{
    /** @internal */
    const ALIAS = 'oro_flat_rate';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return static::ALIAS;
    }
}
