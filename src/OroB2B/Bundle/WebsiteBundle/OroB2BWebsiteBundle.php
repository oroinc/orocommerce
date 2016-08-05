<?php

namespace OroB2B\Bundle\WebsiteBundle;

use OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass;
use OroB2B\Bundle\WebsiteBundle\DependencyInjection\OroB2BWebsiteExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BWebsiteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslationStrategyPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BWebsiteExtension();
        }

        return $this->extension;
    }
}
