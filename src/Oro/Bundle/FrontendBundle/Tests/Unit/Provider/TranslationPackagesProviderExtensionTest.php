<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Provider;

use Oro\Bundle\FrontendBundle\Provider\TranslationPackagesProviderExtension;

class TranslationPackagesProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPackageNames()
    {
        $extension = new TranslationPackagesProviderExtension();
        $this->assertEquals([TranslationPackagesProviderExtension::PACKAGE_NAME], $extension->getPackageNames());
    }
}
