<?php

namespace OroB2B\Bundle\AccountBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler\DataAuditEntityMappingPass;
use OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler\OwnerTreeListenerPass;
use OroB2B\Bundle\AccountBundle\DependencyInjection\OroB2BAccountExtension;

class OroB2BAccountBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OwnerTreeListenerPass());
        $container->addCompilerPass(new DataAuditEntityMappingPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BAccountExtension();
        }

        return $this->extension;
    }
}
