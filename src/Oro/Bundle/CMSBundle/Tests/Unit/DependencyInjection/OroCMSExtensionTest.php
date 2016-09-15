<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;

class OroCMSExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroCMSExtension());

        $expectedParameters = [
            'oro_cms.entity.page.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
