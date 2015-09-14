<?php

namespace OroB2B\Bundle\ProductBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use OroB2B\Bundle\ProductBundle\DependencyInjection\OroB2BProductExtension;

class OroB2BProductBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BProductExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new ComponentProcessorPass());
    }
}
