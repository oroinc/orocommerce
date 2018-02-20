<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCMSExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCMSExtension());

        $expectedParameters = [
            'oro_cms.entity.page.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedExtensionConfigs = [
            'oro_cms',
        ];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }
}
