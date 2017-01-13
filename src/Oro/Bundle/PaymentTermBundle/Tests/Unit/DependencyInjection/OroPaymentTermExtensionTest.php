<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroPaymentTermExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroPaymentTermExtension());

        $expectedParameters = [
            'oro_payment_term.entity.payment_term.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_payment_term.payment_term.manager.api',
            'oro_payment_term.form.type.payment_term',
            'oro_payment_term.integration.channel',
            'oro_payment_term.integration.transport',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
