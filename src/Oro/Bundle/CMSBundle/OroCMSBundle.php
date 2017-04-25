<?php

namespace Oro\Bundle\CMSBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\WidgetTagPass;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

class OroCMSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new WidgetTagPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Page::class => [
                    'slugPrototype' => 'slugPrototypes',
                    'title' => 'titles'
                ],
            ]));
    }
}
