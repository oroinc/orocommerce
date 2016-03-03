<?php

namespace OroB2B\Bundle\OrderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\OrderBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use OroB2B\Bundle\OrderBundle\DependencyInjection\OroB2BOrderExtension;

class OroB2BOrderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BOrderExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SubtotalProviderPass());
    }
}
