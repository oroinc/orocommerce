<?php

namespace Oro\Bundle\FrontendBundle\Provider;

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
}
