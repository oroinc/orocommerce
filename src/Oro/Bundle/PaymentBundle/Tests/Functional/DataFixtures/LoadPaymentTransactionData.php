<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    const AUTHORIZE_TRANSACTION = 'authorize_transaction';
    const VALIDATE_TRANSACTION = 'validate_transaction';
    const AUTHORIZE_ACTIVE_TRANSACTION = 'authorize_active_transaction';

    const PAYMENT_METHOD = 'payment_method';

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData'];
    }

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
                $this->setValue($paymentTransaction, $property, $value);
            }

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }
}
