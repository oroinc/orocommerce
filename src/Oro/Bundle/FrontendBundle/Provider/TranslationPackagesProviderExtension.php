<?php

namespace Oro\Bundle\FrontendBundle\Provider;

use Symfony\Component\Config\FileLocator;

use Oro\Bundle\TranslationBundle\Provider\TranslationPackagesProviderExtensionInterface;

class TranslationPackagesProviderExtension implements TranslationPackagesProviderExtensionInterface
{
    const PACKAGE_NAME = 'OroCommerce';

    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * @param string $rootDirectory
     */
    public function __construct($rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
    }

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
        return new FileLocator([
            $this->rootDirectory . '/../vendor/oro/commerce/src',
            $this->rootDirectory . '/../vendor/oro/commerce-enterprise/src',
            $this->rootDirectory . '/../vendor/oro/customer-portal/src',
        ]);
    }
}
