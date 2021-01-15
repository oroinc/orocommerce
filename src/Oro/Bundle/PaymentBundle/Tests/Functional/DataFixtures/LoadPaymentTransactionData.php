<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    const AUTHORIZE_TRANSACTION = 'authorize_transaction';
    const CHARGE_TRANSACTION = 'charge_transaction';
    const CHARGE_TRANSACTION_FAILED = 'charge_transaction_failed';
    const VALIDATE_TRANSACTION = 'validate_transaction';
    const AUTHORIZE_ACTIVE_TRANSACTION = 'authorize_active_transaction';
    const CHARGED_AUTHORIZE_TRANSACTION = 'charged_authorize_transaction';

    const PAYMENT_METHOD = 'payment_method';

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadCustomerUserData::class];
    }

    /**
     * @var array
     */
    private $referenceProperties = ['sourcePaymentTransactionReference'];

    /**
     * @var array
     */
    protected $data = [
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
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $reference => $data) {
            $paymentTransaction = new PaymentTransaction();

            $data['frontendOwner'] = $this->getReference($data['frontendOwner']);

            foreach ($data as $property => $value) {
                if ($this->isReferenceProperty($property)) {
                    continue;
                }

                $this->setValue($paymentTransaction, $property, $value);
            }

            $this->handleReferenceProperties($paymentTransaction, $data);

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param array              $data
     *
     * @return void
     */
    private function handleReferenceProperties(PaymentTransaction $paymentTransaction, array $data)
    {
        if (array_key_exists('sourcePaymentTransactionReference', $data)) {
            $sourcePaymentTransaction = $this->getReference($data['sourcePaymentTransactionReference']);

            $this->setValue($paymentTransaction, 'sourcePaymentTransaction', $sourcePaymentTransaction);
        }
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    private function isReferenceProperty($property)
    {
        return in_array($property, $this->referenceProperties, true);
    }
}
