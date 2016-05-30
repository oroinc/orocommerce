<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
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
            ['request', ['request', 'data'], []],
            ['response', ['response', 'data'], []],
            ['paymentMethod', 'paymentMethod'],
            ['active', true],
            ['amount', '1000'],
            ['currency', 'USD'],
            ['successful', true],
            ['sourcePaymentTransaction', $this->createPaymentTransaction()],
            ['transactionOptions', ['option'], []],
            ['accessIdentifier', 'accessIdentifier', false],
            ['accessToken', 'accessToken', false],
            ['organization', new Organization()],
            ['frontendOwner', new AccountUser()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
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

    public function testResponseReturnArrayButStoreNull()
    {
        $paymentTransaction = $this->createPaymentTransaction();
        $reflectionProperty = new \ReflectionProperty(get_class($paymentTransaction), 'response');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals(null, $reflectionProperty->getValue($paymentTransaction));
        $this->assertEquals([], $paymentTransaction->getResponse());

        $reflectionProperty->setAccessible(false);
    }

    public function testRequestReturnArrayButStoreNull()
    {
        $paymentTransaction = $this->createPaymentTransaction();
        $reflectionProperty = new \ReflectionProperty(get_class($paymentTransaction), 'request');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals(null, $reflectionProperty->getValue($paymentTransaction));
        $this->assertEquals([], $paymentTransaction->getRequest());

        $reflectionProperty->setAccessible(false);
    }

    public function testTransactionOptionsArrayButStoreNull()
    {
        $paymentTransaction = $this->createPaymentTransaction();
        $reflectionProperty = new \ReflectionProperty(get_class($paymentTransaction), 'transactionOptions');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals(null, $reflectionProperty->getValue($paymentTransaction));
        $this->assertEquals([], $paymentTransaction->getTransactionOptions());

        $reflectionProperty->setAccessible(false);
    }

    /**
     * @return PaymentTransaction
     */
    private function createPaymentTransaction()
    {
        return new PaymentTransaction();
    }
}
