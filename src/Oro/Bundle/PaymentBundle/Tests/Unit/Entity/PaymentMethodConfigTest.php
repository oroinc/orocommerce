<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentMethodConfigTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['methodsConfigsRule', new PaymentMethodsConfigsRule()],
            ['type', 'test'],
            ['options', ['custom' => 'test']],
        ];

        $entity = new PaymentMethodConfig();

        $this->assertPropertyAccessors($entity, $properties);
    }
}
