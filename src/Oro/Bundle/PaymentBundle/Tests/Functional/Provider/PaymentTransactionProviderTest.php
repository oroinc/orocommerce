<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Provider;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTransactionProviderTest extends WebTestCase
{
    public function testGetActiveAuthorizePaymentTransactionShouldNotRelyOnFrontendOwnerFromBackend()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken(self::USER_NAME, self::AUTH_PW, 'user'));

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveAuthorizePaymentTransaction(
                $this->createPaymentTransaction(1),
                '1000',
                'USD',
                'payment_method'
            )
        );
    }

    public function testGetActiveAuthorizePaymentTransactionShouldNotRelyOnFrontendOwner()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::LEVEL_1_1_EMAIL,
                LoadCustomerUserData::LEVEL_1_1_PASSWORD
            )
        );

        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL),
                LoadCustomerUserData::LEVEL_1_1_PASSWORD,
                'key'
            )
        );

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveAuthorizePaymentTransaction(
                $this->createPaymentTransaction(1),
                '1000',
                'USD',
                'payment_method'
            )
        );
    }

    public function testGetActiveValidatePaymentTransactionAnotherUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::LEVEL_1_1_EMAIL,
                LoadCustomerUserData::LEVEL_1_1_PASSWORD
            )
        );

        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL),
                LoadCustomerUserData::LEVEL_1_1_PASSWORD,
                'key'
            )
        );

        $this->assertEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payment_method')
        );
    }

    public function testGetActiveValidatePaymentTransactionCurrentLoggedUserOnly()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadCustomerUserData::EMAIL),
                LoadCustomerUserData::PASSWORD,
                'key'
            )
        );

        $this->assertNotEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payment_method')
        );
    }

    public function testGetActiveValidatePaymentTransactionEmptyForUser()
    {
        $this->initClient();
        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(null);

        $this->assertEmpty(
            $paymentTransactionProvider->getActiveValidatePaymentTransaction('payment_method')
        );
    }

    public function testCreatePaymentTransactionUseCurrentLoggedCustomerUser()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $this->getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $this->getReference(LoadCustomerUserData::EMAIL),
                LoadCustomerUserData::PASSWORD,
                'key'
            )
        );

        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            $this->createPaymentTransaction(1)
        );

        $paymentTransaction
            ->setAmount('1000')
            ->setCurrency('USD');

        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $this->assertEquals(LoadCustomerUserData::EMAIL, $paymentTransaction->getFrontendOwner()->getEmail());
    }

    public function testTransactionSaveDatabaseException()
    {
        $this->expectException(NotNullConstraintViolationException::class);

        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('critical');

        $paymentTransactionProvider->setLogger($logger);
        $paymentTransactionProvider->savePaymentTransaction(new PaymentTransaction());
    }

    public function testCreatePaymentTransactionNonManageable()
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
            'paymentMethod',
            'authorize',
            new \stdClass()
        );
        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }

    public function testCreatePaymentTransactionByParentTransaction()
    {
        $this->initClient();

        $paymentTransactionProvider = $this->getContainer()->get('oro_payment.provider.payment_transaction');

        $action = 'someAction';
        $paymentMethod = 'somePaymentMethod';
        $entityClass = 'someEntityClass';
        $entityIdentifier = 1;
        $amount = 'someAmount';
        $currency = 'someCurrency';

        $parentPaymentTransaction = new PaymentTransaction();
        $parentPaymentTransaction
            ->setPaymentMethod($paymentMethod)
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityIdentifier)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setAccessToken(null)
            ->setAccessIdentifier(null);

        $expectedPaymentTransaction = clone $parentPaymentTransaction;
        $expectedPaymentTransaction
            ->setAction($action)
            ->setFrontendOwner($this->getLoggedCustomerUser())
            ->setSourcePaymentTransaction($parentPaymentTransaction);

        $actualPaymentTransaction = $paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            $action,
            $parentPaymentTransaction
        );

        $actualPaymentTransaction
            ->setAccessToken(null)
            ->setAccessIdentifier(null);

        $this->assertEquals($expectedPaymentTransaction, $actualPaymentTransaction);
    }

    public function testCreateTransactionForAnonymousCustomerUser()
    {
        $this->initClient();
        $this->loadFixtures([LoadPaymentTransactionData::class]);

        $customerUser = new CustomerUser();
        $customerUser->setEmail('test_guest@example.com');

        $visitor = new CustomerVisitor();
        $visitor->setCustomerUser($customerUser);

        $this->getContainer()
            ->get('security.token_storage')
            ->setToken(new AnonymousCustomerUserToken(self::USER_NAME, [], $visitor));

        $paymentTransaction = $this->getContainer()
            ->get('oro_payment.provider.payment_transaction')
            ->createPaymentTransaction(
                'payment_method',
                PaymentMethodInterface::PURCHASE,
                $this->createPaymentTransaction(1)
            );

        $this->assertNotNull($paymentTransaction->getFrontendOwner());
        $this->assertEquals($customerUser->getEmail(), $paymentTransaction->getFrontendOwner()->getEmail());
    }

    private function createPaymentTransaction(int $id): PaymentTransaction
    {
        $paymentTransaction = new PaymentTransaction();
        ReflectionUtil::setId($paymentTransaction, $id);

        return $paymentTransaction;
    }

    private function getLoggedCustomerUser(): ?CustomerUser
    {
        $token = $this->getContainer()->get('security.token_storage')->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof CustomerUser) {
            return $user;
        }

        return null;
    }
}
