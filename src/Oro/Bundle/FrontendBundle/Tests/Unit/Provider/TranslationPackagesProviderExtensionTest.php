<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Provider;

use Oro\Bundle\FrontendBundle\OroFrontendBundle;
use Oro\Bundle\FrontendBundle\Provider\TranslationPackagesProviderExtension;
use Oro\Bundle\TranslationBundle\Tests\Unit\Provider\TranslationPackagesProviderExtensionTestAbstract;

class TranslationPackagesProviderExtensionTest extends TranslationPackagesProviderExtensionTestAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function createExtension()
    {
        return new TranslationPackagesProviderExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackagesName()
    {
        return [TranslationPackagesProviderExtension::PACKAGE_NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function packagePathProvider()
    {
        return [
            [
                'path' => str_replace('\\', '/', sprintf('%s.php', OroFrontendBundle::class))
            ]
        ];
    }
}
