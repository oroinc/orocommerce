<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    public const AUTHORIZE_TRANSACTION = 'authorize_transaction';
    public const CHARGE_TRANSACTION = 'charge_transaction';
    public const CHARGE_TRANSACTION_FAILED = 'charge_transaction_failed';
    public const VALIDATE_TRANSACTION = 'validate_transaction';
    public const AUTHORIZE_ACTIVE_TRANSACTION = 'authorize_active_transaction';
    public const CHARGED_AUTHORIZE_TRANSACTION = 'charged_authorize_transaction';

    public const PAYMENT_METHOD = 'payment_method';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadCustomerUserData::class];
    }

    private array $referenceProperties = ['sourcePaymentTransactionReference'];

    protected array $data = [
        self::AUTHORIZE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::VALIDATE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::VALIDATE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::AUTHORIZE_ACTIVE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::CHARGED_AUTHORIZE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::CHARGE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::CHARGE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            'sourcePaymentTransactionReference' => self::CHARGED_AUTHORIZE_TRANSACTION
        ],
        self::CHARGE_TRANSACTION_FAILED => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::CHARGE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => false,
            'paymentMethod' => self::PAYMENT_METHOD,
            'entityClass' => PaymentTransaction::class,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            'sourcePaymentTransactionReference' => self::CHARGED_AUTHORIZE_TRANSACTION
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->data as $reference => $data) {
            $data['frontendOwner'] = $this->getReference($data['frontendOwner']);

            $paymentTransaction = new PaymentTransaction();
            foreach ($data as $property => $value) {
                if (\in_array($property, $this->referenceProperties, true)) {
                    continue;
                }
                $propertyAccessor->setValue($paymentTransaction, $property, $value);
            }
            if (\array_key_exists('sourcePaymentTransactionReference', $data)) {
                $sourcePaymentTransaction = $this->getReference($data['sourcePaymentTransactionReference']);
                $propertyAccessor->setValue($paymentTransaction, 'sourcePaymentTransaction', $sourcePaymentTransaction);
            }

            $this->setReference($reference, $paymentTransaction);
            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }
}
