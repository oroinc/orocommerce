<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData;
use Oro\Component\Testing\Unit\EntityTrait;

class LoadPaymentAuthorizeTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    const AUTHORIZE_TRANSACTION = 'authorize_transaction';

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [
            LoadCustomerUserData::class,
            LoadPayPalChannelData::class,
            LoadOrders::class
        ];
    }

    /**
     * @var array
     */
    protected $data = [
        self::AUTHORIZE_TRANSACTION => [
            'amount' => '1000.00',
            'currency' => 'USD',
            'action' => PaymentMethodInterface::AUTHORIZE,
            'frontendOwner' => LoadCustomerUserData::EMAIL,
            'response' => [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            'paymentMethod' => 'paymentMethod',
            'entityClass' => Order::class,
            'entity_reference' => LoadOrders::ORDER_1,
            'channel_reference' => 'paypal:channel_1',
            'method_prefix' => 'paypal_payflow_gateway_credit_card',
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

            $order = $this->getReference($data['entity_reference']);

            $paymentTransaction->setEntityIdentifier($order->getId());

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }
}
