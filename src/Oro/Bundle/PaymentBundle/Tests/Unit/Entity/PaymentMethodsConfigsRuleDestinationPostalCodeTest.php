<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentMethodsConfigsRuleDestinationPostalCodeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['name', 'test'],
            ['destination', new PaymentMethodsConfigsRuleDestination()],
        ];

        $entity = new PaymentMethodsConfigsRuleDestinationPostalCode();

        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testToString()
    {
        $code = new PaymentMethodsConfigsRuleDestinationPostalCode();
        $code->setName('123');
        $this->assertEquals('123', (string)$code);
    }
}
