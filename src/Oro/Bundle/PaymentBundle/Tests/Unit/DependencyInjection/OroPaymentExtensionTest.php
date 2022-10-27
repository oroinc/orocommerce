<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\Controller\Api\Rest\PaymentMethodsConfigsRuleController;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPaymentExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroPaymentExtension());

        $expectedDefinitions = [
            PaymentMethodsConfigsRuleController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
