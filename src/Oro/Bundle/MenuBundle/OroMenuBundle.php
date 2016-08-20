<?php

namespace Oro\Bundle\MenuBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\MenuBundle\DependencyInjection\Compiler\ConditionExpressionLanguageProvidersCompilerPass;
use Oro\Bundle\MenuBundle\DependencyInjection\OroMenuExtension;
use Oro\Bundle\MenuBundle\Entity\MenuItem;

class OroMenuBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroMenuExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new ConditionExpressionLanguageProvidersCompilerPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                MenuItem::class => ['title' => 'titles'],
            ]));
    }
}
