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
            'oro_payment_term.form.type.payment_term'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
