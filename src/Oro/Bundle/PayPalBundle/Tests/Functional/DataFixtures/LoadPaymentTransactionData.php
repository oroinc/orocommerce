<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PayPalBundle\Method\PayflowGateway;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    const PAYFLOW_AUTHORIZE_TRANSACTION = 'payflow_authorize_transaction';
    const PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER = 'payflow_authorize_transaction_ip_filter';

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData'];
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
            'entityClass' => 'Oro\Bundle\PaymentBundle\Entity\PaymentTerm',
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
            'entityClass' => 'Oro\Bundle\PaymentBundle\Entity\PaymentTerm',
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
