<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * @dbIsolation
 */
class PaymentTransactionProviderTest extends WebTestCase
{
    use EntityTrait;

    public function testGetActiveAuthorizePaymentTransactionShouldNotRelyOnFrontendOwnerFromBackend()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData',
                'Oro\Bundle\TestFrameworkBundle\Fixtures\LoadUserData',
            ]
        );

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($this->getReference('default_user'), 'password', 'key'));

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveAuthorizePaymentTransaction(
                $this->getEntity('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm', ['id' => 1]),
                '1000',
                'USD',
                'payflow_gateway'
            )
        );
    }

    public function testGetActiveAuthorizePaymentTransactionShouldNotRelyOnFrontendOwner()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadAccountUserData::LEVEL_1_1_EMAIL,
                LoadAccountUserData::LEVEL_1_1_PASSWORD
            )
        );

        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadAccountUserData::LEVEL_1_1_EMAIL),
                LoadAccountUserData::LEVEL_1_1_PASSWORD,
                'key'
            )
        );

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveAuthorizePaymentTransaction(
                $this->getEntity('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm', ['id' => 1]),
                '1000',
                'USD',
                'payflow_gateway'
            )
        );
    }

    public function testGetActiveValidatePaymentTransactionAnotherUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadAccountUserData::LEVEL_1_1_EMAIL,
                LoadAccountUserData::LEVEL_1_1_PASSWORD
            )
        );

        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadAccountUserData::LEVEL_1_1_EMAIL),
                LoadAccountUserData::LEVEL_1_1_PASSWORD,
                'key'
            )
        );

        $this->assertEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payflow_gateway')
        );
    }

    public function testGetActiveValidatePaymentTransactionCurrentLoggedUserOnly()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadAccountUserData::EMAIL),
                LoadAccountUserData::PASSWORD,
                'key'
            )
        );

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payflow_gateway')
        );
    }

    public function testGetActiveValidatePaymentTransactionEmptyForUser()
    {
        $this->initClient();
        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(null);

        $this->assertEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payflow_gateway')
        );
    }

    public function testCreatePaymentTransactionUseCurrentLoggedAccountUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadAccountUserData::EMAIL),
                LoadAccountUserData::PASSWORD,
                'key'
            )
        );

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            $this->getEntity('OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm', ['id' => 1])
        );

        $paymentTransaction
            ->setAmount('1000')
            ->setCurrency('USD');

        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $this->assertEquals(LoadAccountUserData::EMAIL, $paymentTransaction->getFrontendOwner()->getEmail());
    }

    public function testTransactionSaveExceptionDoNotBreakThings()
    {
        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('orob2b_payment.provider.payment_transaction');
        /** @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface $logger */
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface $logger */
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('error');

        $paymentTransactionProvider->setLogger($logger);
        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            new PaymentTerm()
        );
        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
