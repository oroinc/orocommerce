<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    use EntityTrait;

    /**
     * References
     */
    const PAYFLOW_AUTHORIZE_TRANSACTION = 'payflow_authorize_transaction';
    const PAYFLOW_CHARGE_TRANSACTION = 'payflow_charge_transaction';

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadCustomerUserData::class,
            LoadPayPalChannelData::class,
            LoadOrders::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $reference => $data) {
            $paymentTransaction = new PaymentTransaction();

            foreach ($data as $property => $value) {
                $this->setValue($paymentTransaction, $property, $value);
            }

            $this->setReference($reference, $paymentTransaction);

            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    private function getData()
    {
        /** @var CustomerUser $frontendOwner */
        $frontendOwner = $this->getReference(LoadCustomerUserData::EMAIL);

        /** @var Order $order1 */
        $order1 = $this->getReference(LoadOrders::ORDER_1);

        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::ORDER_2);

        /** @var Channel $channel */
        $channel = $this->getReference(LoadPayPalChannelData::PAYPAL_PAYFLOW_GATAWAY1);

        return [
            self::PAYFLOW_AUTHORIZE_TRANSACTION => [
                'amount' => '1000.00',
                'currency' => 'USD',
                'action' => PaymentMethodInterface::AUTHORIZE,
                'entityIdentifier' => $order1->getId(),
                'entityClass' => Order::class,
                'frontendOwner' => $frontendOwner,
                'response' => [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                'payment_method' => 'paypal_payflow_gateway_credit_card_' . $channel->getId(),
                'active' => true,
                'successful' => true,
            ],
            self::PAYFLOW_CHARGE_TRANSACTION => [
                'amount' => '1000.00',
                'currency' => 'USD',
                'action' => PaymentMethodInterface::CHARGE,
                'entityIdentifier' => $order2->getId(),
                'entityClass' => Order::class,
                'frontendOwner' => $frontendOwner,
                'response' => [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                'payment_method' => 'paypal_payflow_gateway_credit_card_' . $channel->getId(),
                'active' => true,
                'successful' => true,
            ],
        ];
    }
}
