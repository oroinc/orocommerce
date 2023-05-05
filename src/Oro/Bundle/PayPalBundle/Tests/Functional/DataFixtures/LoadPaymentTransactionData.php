<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\Channel as IntegrationChannel;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class LoadPaymentTransactionData extends AbstractFixture implements DependentFixtureInterface
{
    public const PAYFLOW_AUTHORIZE_TRANSACTION = 'payflow_authorize_transaction';
    public const PAYFLOW_CHARGE_TRANSACTION = 'payflow_charge_transaction';
    public const PAYMENTS_PRO_EC_AUTHORIZE_PENDING_TRANSACTION = 'payments_pro_ec_authorize_pending_transaction';
    public const PAYMENTS_PRO_EC_AUTHORIZE_PAID_TRANSACTION = 'payments_pro_ec_authorize_paid_transaction';

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->getData() as $reference => $data) {
            $paymentTransaction = new PaymentTransaction();
            foreach ($data as $property => $value) {
                $propertyAccessor->setValue($paymentTransaction, $property, $value);
            }
            $this->setReference($reference, $paymentTransaction);
            $manager->persist($paymentTransaction);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        /** @var CustomerUser $frontendOwner */
        $frontendOwner = $this->getReference(LoadCustomerUserData::EMAIL);
        /** @var Order $order1 */
        $order1 = $this->getReference(LoadOrders::ORDER_1);
        /** @var Order $order2 */
        $order2 = $this->getReference(LoadOrders::ORDER_2);
        /** @var Order $order3 */
        $order3 = $this->getReference(LoadOrders::ORDER_3);
        /** @var Order $order4 */
        $order4 = $this->getReference(LoadOrders::ORDER_4);
        /** @var Channel $channel */
        $channel = $this->getReference(LoadPayPalChannelData::PAYPAL_PAYFLOW_GATAWAY1);
        /** @var IntegrationChannel $paymentsPro1Channel */
        $paymentsPro1Channel = $this->getReference(LoadPayPalChannelData::PAYPAL_PAYMENTS_PRO1);

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
            self::PAYMENTS_PRO_EC_AUTHORIZE_PENDING_TRANSACTION => [
                'amount' => '1000.00',
                'currency' => 'USD',
                'action' => PaymentMethodInterface::AUTHORIZE,
                'entityIdentifier' => $order3->getId(),
                'entityClass' => Order::class,
                'frontendOwner' => $frontendOwner,
                'response' => [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                'transactionOptions' => [
                    'successUrl' => 'https://example.com/success-url',
                    'failureUrl' => 'https://example.com/failure-url'
                ],
                'payment_method' => 'paypal_payments_pro_express_checkout_' . $paymentsPro1Channel->getId(),
                'active' => true,
                'successful' => false,
            ],
            self::PAYMENTS_PRO_EC_AUTHORIZE_PAID_TRANSACTION => [
                'amount' => '1000.00',
                'currency' => 'USD',
                'action' => PaymentMethodInterface::AUTHORIZE,
                'entityIdentifier' => $order4->getId(),
                'entityClass' => Order::class,
                'frontendOwner' => $frontendOwner,
                'response' => [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                'transactionOptions' => [
                    'successUrl' => 'https://example.com/success-url',
                    'failureUrl' => 'https://example.com/failure-url'
                ],
                'payment_method' => 'paypal_payments_pro_express_checkout_' . $paymentsPro1Channel->getId(),
                'active' => true,
                'successful' => true,
            ],
        ];
    }
}
