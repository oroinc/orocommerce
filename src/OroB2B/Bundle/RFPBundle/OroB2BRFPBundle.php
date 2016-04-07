<?php

namespace OroB2B\Bundle\RFPBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;
use OroB2B\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;
use OroB2B\Bundle\RFPBundle\DependencyInjection\CompilerPass\OrderBundlePass;
use OroB2B\Bundle\RFPBundle\DependencyInjection\OroB2BRFPExtension;

class OroB2BRFPBundle extends Bundle
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
            $this->extension = new OroB2BRFPExtension();
        }

        return $this->extension;
    }
}
