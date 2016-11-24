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
            'oro_payment.entity.payment_transaction.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_payment.formatter.payment_method_label',
            'oro_payment.twig.payment_method_extension',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
