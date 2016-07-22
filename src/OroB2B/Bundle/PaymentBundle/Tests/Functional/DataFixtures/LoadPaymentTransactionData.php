<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    const PAYFLOW_AUTHORIZE_TRANSACTION = 'payflow_transaction';
    const PAYFLOW_VALIDATE_TRANSACTION = 'payflow_validate_transaction';
    const PAYFLOW_AUTHORIZE_ACTIVE_TRANSACTION = 'payflow_authorize_active_transaction';
    const PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER = 'payflow_authorize_transaction_ip_filter';

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData'];
    }

    /**
     * @var array
     */
    protected $data = [
        self::PAYFLOW_AUTHORIZE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'paymentMethod' => PayflowGateway::TYPE,
            'entityClass' => 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
            'frontendOwner' => LoadAccountUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::PAYFLOW_VALIDATE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::VALIDATE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => PayflowGateway::TYPE,
            'entityClass' => 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
            'frontendOwner' => LoadAccountUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::PAYFLOW_AUTHORIZE_ACTIVE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'active' => true,
            'successful' => true,
            'paymentMethod' => PayflowGateway::TYPE,
            'entityClass' => 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
            'frontendOwner' => LoadAccountUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
        self::PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'paymentMethod' => PayflowGateway::TYPE,
            'entityClass' => 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm',
            'frontendOwner' => LoadAccountUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->data as $reference => $data) {
            $paymentTransaction = new PaymentTransaction();

            $data['frontendOwner'] = $this->getReference($data['frontendOwner']);

            foreach ($data as $property => $value) {
                try {
                    $propertyAccessor->setValue($paymentTransaction, $property, $value);
                } catch (NoSuchPropertyException $e) {
                }
            }

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }
}
