<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FrontendLocalizationBundle\DependencyInjection\OroFrontendLocalizationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFrontendLocalizationExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroFrontendLocalizationExtension());

        $expectedDefinitions = [
            'oro_frontend_localization.user_localization_manager',
            'oro_frontend_localization.acl.voter.localization',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
