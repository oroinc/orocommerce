<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentTransactionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['reference', 'reference'],
            ['state', 'state'],
            ['type', 'type'],
            ['entityClass', 'entityClass'],
            ['entityIdentifier', 1],
            ['data', ['value']],
        ];

        $this->assertPropertyAccessors($this->createPaymentTransaction(), $properties);
    }

    /**
     * @return PaymentTransaction
     */
    private function createPaymentTransaction()
    {
        return new PaymentTransaction();
    }
}
