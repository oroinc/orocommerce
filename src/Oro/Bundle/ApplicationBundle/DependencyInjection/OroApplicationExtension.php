<?php

namespace Oro\Bundle\ApplicationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApplicationBundle\Configuration\RolesConfiguration;

class OroApplicationExtension extends Extension
{
    const CONFIG_ROLES = 'roles.yml';
    const ROLES_CONTAINER_PARAM_FRONTEND = 'security.role_hierarchy.roles';
    const ROLES_CONTAINER_PARAM_ADMIN = 'frontend.roles';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('twig.yml');

        $roles = $this->parseExternalConfigFiles($container);
        $application = $container->getParameter('kernel.application');

        if ($application == \AppKernel::APPLICATION_FRONTEND) {
            $roles = array_map(
                function () {
                    return [];
                },
                $roles
            );

            $roles = array_merge_recursive($roles, $container->getParameter(self::ROLES_CONTAINER_PARAM_FRONTEND));

            $container->setParameter(self::ROLES_CONTAINER_PARAM_FRONTEND, $roles);
        } elseif ($application == \AppKernel::APPLICATION_ADMIN) {
            $container->setParameter(self::ROLES_CONTAINER_PARAM_ADMIN, $roles);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return array
     */
    protected function parseExternalConfigFiles(ContainerBuilder $container)
    {
        $rootDir = $container->getParameter('kernel.root_dir');
        $files = $this->findConfigurations(
            self::CONFIG_ROLES,
            [
                $rootDir . '/../src',
                $rootDir . '/../vendor'
            ]
        );

        $configs = [];

        foreach ($files as $file) {
            $configs[] = Yaml::parse($file);
        }

        $processor = new Processor();
        return $processor->processConfiguration(new RolesConfiguration(), $configs);
    }

    /**
     * @param string $targetFile
     * @param array $roots
     * @return array
     */
    protected function findConfigurations($targetFile, $roots = [])
    {
        $paths = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $root   = realpath($root);
            $dir    = new \RecursiveDirectoryIterator($root, \FilesystemIterator::FOLLOW_SYMLINKS);
            $filter = new \RecursiveCallbackFilterIterator(
                $dir,
                function (\SplFileInfo $current) use (&$paths, $targetFile) {
                    $fileName = strtolower($current->getFilename());

                    if ($fileName === '.' || $fileName === '..' || $fileName === 'tests' || $current->isFile()) {
                        return false;
                    }

                    if (!is_dir($current->getPathname() . '/Resources')) {
                        return true;
                    } else {
                        $file = $current->getPathname() . '/Resources/config/oro/' . $targetFile;
                        if (is_file($file)) {
                            $paths[] = $file;
                        }

                        return false;
                    }
                }
            );

            $iterator = new \RecursiveIteratorIterator($filter);
            $iterator->rewind();
        }

        return $paths;
    }
}
