<?php

namespace Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ApplicationHostPass implements CompilerPassInterface
{
    const PARAMETER_NAME = 'application_hosts';

    /**
     * @var array
     */
    private $parameters = [
        'application_host.admin',
        'application_host.frontend',
        'application_host.installer',
        'application_host.tracking',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $hosts = [];

        foreach ($this->parameters as $parameter) {
            if (!$container->hasParameter($parameter)) {
                throw new \RuntimeException(sprintf('Parameter `%s` must be defined.', $parameter));
            }

            $parts = explode('.', $parameter);

            $hosts[$parts[1]] = $container->getParameter($parameter);
        }

        $container->setParameter(self::PARAMETER_NAME, $hosts);
    }
}
