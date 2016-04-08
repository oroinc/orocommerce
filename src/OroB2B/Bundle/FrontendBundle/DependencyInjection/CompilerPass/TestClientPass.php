<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestClientPass implements CompilerPassInterface
{
    const TEST_CLIENT_CLASS = 'test.client.class';
    const TEST_CLIENT_VALUE = 'OroB2B\Bundle\FrontendBundle\Test\Client';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(static::TEST_CLIENT_CLASS)) {
            $container->setParameter(static::TEST_CLIENT_CLASS, static::TEST_CLIENT_VALUE);
        }
    }
}
