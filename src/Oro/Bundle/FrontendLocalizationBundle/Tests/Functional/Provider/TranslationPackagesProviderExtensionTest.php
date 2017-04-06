<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Provider;

use Oro\Bundle\TranslationBundle\Tests\Functional\Provider\TranslationPackagesProviderExtensionTestAbstract;

class TranslationPackagesProviderExtensionTest extends TranslationPackagesProviderExtensionTestAbstract
{
    /**
     * {@inheritdoc}
     */
    public function expectedPackagesDataProvider()
    {
        yield 'OroCommerce Package' => [
            'packageName' => 'OroCommerce',
            'fileToLocate' => 'Oro/Bundle/FrontendLocalizationBundle/OroFrontendLocalizationBundle.php'
        ];
    }
}
