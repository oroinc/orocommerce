<?php

namespace OroB2B\Bundle\WebsiteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass;
use OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use OroB2B\Bundle\WebsiteBundle\DependencyInjection\OroB2BWebsiteExtension;

class OroB2BWebsiteBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslationStrategyPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
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