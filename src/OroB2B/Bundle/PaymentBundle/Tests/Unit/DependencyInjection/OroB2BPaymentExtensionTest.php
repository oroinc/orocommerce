<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class OroB2BPaymentExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BPaymentExtension());

        $expectedParameters = [
            'orob2b_payment.entity.payment_term.class',
            'orob2b_payment.entity.payment_term.api.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_payment.payment_term.manager.api',
            'orob2b_payment.form.type.payment_term'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
