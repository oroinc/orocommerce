<?php

namespace Oro\Bundle\RFPBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;
use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;
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
        $container->addCompilerPass(new DuplicatorFilterPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new DuplicatorMatcherPass(), PassConfig::TYPE_AFTER_REMOVING);
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
