<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class PaymentTermTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['label', 'net 10']
        ];

        $this->assertPropertyAccessors($this->createPaymentTerm(), $properties);
    }

    public function testToString()
    {
        $entity = new PaymentTerm();
        $this->assertEmpty((string)$entity);
        $entity->setLabel('test');
        $this->assertEquals('test', (string)$entity);
    }

    /**
     * @return PaymentTerm
     */
    private function createPaymentTerm()
    {
        return new PaymentTerm();
    }
}
