<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;

class LoadPaymentTransactionData extends AbstractFixture
{
    const PAYFLOW_TRANSACTION = 'payflow_transaction';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction(PayflowGateway::AUTHORIZE)
            ->setEntityIdentifier(1)
            ->setEntityClass('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm')
            ->setPaymentMethod(PayflowGateway::TYPE)
            ->setAmount('1000')
            ->setCurrency('USD')
            ->setResponse(
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ]
            );

        $this->setReference(self::PAYFLOW_TRANSACTION, $paymentTransaction);

        $manager->persist($paymentTransaction);
        $manager->flush();
    }
}
