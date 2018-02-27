<?php

namespace Oro\Bundle\RFPBundle;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\OrderBundlePass;
use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroRFPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new OrderBundlePass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroRFPExtension();
        }

        return $this->extension;
    }
}
