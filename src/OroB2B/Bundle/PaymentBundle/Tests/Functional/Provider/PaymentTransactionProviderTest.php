<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * @dbIsolation
 */
class PaymentTransactionProviderTest extends WebTestCase
{
    public function testTransactionSaveExceptionDoNotBreakThings()
    {
        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('error');

        $paymentTransactionProvider->setLogger($logger);
        $paymentTransactionProvider->savePaymentTransaction(new PaymentTransaction());
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\NotManageableEntityException
     * @expectedExceptionMessage Entity class "stdClass" is not manageable.
     */
    public function testCreatePaymentTransactionNonManageable()
    {
        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            new \stdClass()
        );
        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }

    public function testCreatePaymentTransactionWithoutId()
    {
        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('error');

        $paymentTransactionProvider->setLogger($logger);
        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            new Order()
        );
        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
