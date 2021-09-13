<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\Controller\Api\Rest\ShippingMethodsConfigsRuleController;
use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroShippingExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroShippingExtension());

        $expectedDefinitions = [
            ShippingMethodsConfigsRuleController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
