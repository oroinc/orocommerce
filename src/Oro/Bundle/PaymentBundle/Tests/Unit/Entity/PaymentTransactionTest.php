<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentTransactionTest extends \PHPUnit\Framework\TestCase
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
            ['owner', new User()],
            ['frontendOwner', new CustomerUser()],
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

        $this->assertNull(ReflectionUtil::getPropertyValue($paymentTransaction, 'response'));
        $this->assertEquals([], $paymentTransaction->getResponse());
    }

    public function testRequestReturnArrayButStoreNull()
    {
        $paymentTransaction = $this->createPaymentTransaction();

        $this->assertNull(ReflectionUtil::getPropertyValue($paymentTransaction, 'request'));
        $this->assertEquals([], $paymentTransaction->getRequest());
    }

    public function testTransactionOptionsArrayButStoreNull()
    {
        $paymentTransaction = $this->createPaymentTransaction();

        $this->assertNull(ReflectionUtil::getPropertyValue($paymentTransaction, 'transactionOptions'));
        $this->assertEquals([], $paymentTransaction->getTransactionOptions());
    }

    /**
     * @return PaymentTransaction
     */
    private function createPaymentTransaction()
    {
        return new PaymentTransaction();
    }

    public function testNotACloneIfHasNotSourceTransaction()
    {
        $this->assertFalse($this->createPaymentTransaction()->isClone());
    }

    public function testNotACloneIfSourceTransactionIsNotValidateOne()
    {
        $sourcePaymentTransaction = $this->createPaymentTransaction();
        $sourcePaymentTransaction
            ->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction = $this->createPaymentTransaction();
        $paymentTransaction
            ->setSourcePaymentTransaction($sourcePaymentTransaction);

        $this->assertFalse($paymentTransaction->isClone());
    }

    public function testCloneIfSourceTransactionIsValidateOne()
    {
        $sourcePaymentTransaction = $this->createPaymentTransaction();
        $sourcePaymentTransaction
            ->setAction(PaymentMethodInterface::VALIDATE);
        $paymentTransaction = $this->createPaymentTransaction();
        $paymentTransaction
            ->setSourcePaymentTransaction($sourcePaymentTransaction);

        $this->assertTrue($paymentTransaction->isClone());
    }
}
