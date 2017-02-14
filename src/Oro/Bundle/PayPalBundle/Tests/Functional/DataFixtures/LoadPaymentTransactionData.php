<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    const PAYFLOW_AUTHORIZE_TRANSACTION = 'payflow_authorize_transaction';
    const PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER = 'payflow_authorize_transaction_ip_filter';

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData',
            'Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData',
        ];
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
            'entityClass' => '\stdClass',
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            'channel_reference' => 'paypal:channel_1',
            'method_prefix' => 'paypal_payflow_gateway_credit_card'
        ],
        self::PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'entityIdentifier' => 1,
            'entityClass' => '\stdClass',
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            'channel_reference' => 'paypal:channel_1',
            'method_prefix' => 'paypal_payflow_gateway_credit_card'
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
                if ($this->getPropertyAccessor()->isWritable($paymentTransaction, $property)) {
                    $this->setValue($paymentTransaction, $property, $value);
                }
            }

            $channel = $this->getReference($data['channel_reference']);
            $paymentMethod = sprintf('%s_%s', $data['method_prefix'], $channel->getId());

            $paymentTransaction->setPaymentMethod($paymentMethod);

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }
}
