<?php

namespace OroB2B\Bundle\WebsiteBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass;

class OroB2BWebsiteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslationStrategyPass());
    }
}
