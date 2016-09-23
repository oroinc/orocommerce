<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;

class OroPaymentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPaymentExtension());

        $expectedParameters = [
            'oro_payment.entity.payment_term.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_payment.payment_term.manager.api',
            'oro_payment.form.type.payment_term'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
