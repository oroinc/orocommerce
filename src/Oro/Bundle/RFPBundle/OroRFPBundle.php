<?php

namespace Oro\Bundle\RFPBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\OrderBundlePass;
use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;

class OroRFPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

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
