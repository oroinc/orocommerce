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
            ['action', 'action'],
            ['entityClass', 'entityClass'],
            ['entityIdentifier', 1],
            ['request', ['request', 'data']],
            ['response', ['response', 'data']],
            ['paymentMethod', 'paymentMethod'],
            ['active', true],
            ['amount', '1000'],
            ['currency', 'USD'],
            ['successful', true],
            ['sourcePaymentTransaction', $this->createPaymentTransaction()],
            ['transactionOptions', ['option']],
            ['accessIdentifier', 'accessIdentifier', false],
            ['accessToken', 'accessToken', false],
        ];

        $this->assertPropertyAccessors($this->createPaymentTransaction(), $properties);
    }

    public function testRelations()
    {
        $properties = [
            ['relatedPaymentTransactions', $this->createPaymentTransaction()],
        ];

        $this->assertPropertyCollections($this->createPaymentTransaction(), $properties);
    }

    /**
     * @return PaymentTransaction
     */
    private function createPaymentTransaction()
    {
        return new PaymentTransaction();
    }
}
