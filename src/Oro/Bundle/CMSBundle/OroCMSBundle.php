<?php

namespace Oro\Bundle\CMSBundle;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCMSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Page::class => [
                    'slug' => 'slugs',
                    'title' => 'titles'
                ],
            ]));
    }
}
