<?php

namespace Oro\Bundle\AccountBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AccountBundle\DependencyInjection\Compiler\DataAuditEntityMappingPass;
use Oro\Bundle\AccountBundle\DependencyInjection\Compiler\OwnerTreeListenerPass;
use Oro\Bundle\AccountBundle\DependencyInjection\Compiler\WindowsStateManagerPass;
use Oro\Bundle\AccountBundle\DependencyInjection\OroAccountExtension;

class OroAccountBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OwnerTreeListenerPass());
        $container->addCompilerPass(new DataAuditEntityMappingPass());
        $container->addCompilerPass(new WindowsStateManagerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroAccountExtension();
        }

        return $this->extension;
    }
}
