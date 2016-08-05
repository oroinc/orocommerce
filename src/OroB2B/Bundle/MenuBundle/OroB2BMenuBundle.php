<?php

namespace OroB2B\Bundle\MenuBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

use OroB2B\Bundle\MenuBundle\DependencyInjection\Compiler\ConditionExpressionLanguageProvidersCompilerPass;
use OroB2B\Bundle\MenuBundle\DependencyInjection\OroB2BMenuExtension;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class OroB2BMenuBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BMenuExtension();
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
