<?php

namespace Oro\Bundle\FrontendBundle\Provider;

use Symfony\Component\Config\FileLocator;

use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

class TranslationPackagesProviderExtension implements TranslationPackagesProviderExtensionInterface
{
    const PACKAGE_NAME = 'OroCommerce';

    /**
     * {@inheritdoc}
     */
    public function getPackageNames()
    {
        return [self::PACKAGE_NAME];
    }

    /**
     * @return FileLocator
     */
    public function getPackagePaths()
    {
        return new FileLocator(__DIR__ . '/../../../../');
    }
}
