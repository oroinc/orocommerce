<?php

namespace OroB2B\Bundle\AttributeBundle;

use OroB2B\Bundle\AttributeBundle\DependencyInjection\Compiler\AttributeProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BAttributeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AttributeProviderPass());
    }
}
