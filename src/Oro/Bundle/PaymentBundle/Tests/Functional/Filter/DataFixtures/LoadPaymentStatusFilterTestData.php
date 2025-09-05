<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Filter\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class LoadPaymentStatusFilterTestData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string ORDER_PAID_IN_FULL = 'order_paid_in_full';
    public const string ORDER_PAID_PARTIALLY = 'order_paid_partially';
    public const string ORDER_PENDING = 'order_pending';
    public const string ORDER_PAYMENT_FAILED = 'order_payment_failed';
    public const string ORDER_CANCELED = 'order_canceled';
    public const string ORDER_REFUNDED = 'order_refunded';
    public const string ORDER_AUTHORIZED = 'order_authorized';
    public const string ORDER_NO_PAYMENT_STATUS = 'order_no_payment_status';
    public const string ORDER_FORCED_STATUS = 'order_forced_status';

    private array $orders = [
        self::ORDER_PAID_IN_FULL => [
            'identifier' => 'ORDER-PAID-FULL',
            'poNumber' => 'PO-PAID-FULL',
            'currency' => 'USD',
            'subtotal' => '100.00',
            'total' => '110.00',
            'paymentStatus' => PaymentStatuses::PAID_IN_FULL,
            'forced' => false,
        ],
        self::ORDER_PAID_PARTIALLY => [
            'identifier' => 'ORDER-PAID-PARTIAL',
            'poNumber' => 'PO-PAID-PARTIAL',
            'currency' => 'EUR',
            'subtotal' => '200.00',
            'total' => '220.00',
            'paymentStatus' => PaymentStatuses::PAID_PARTIALLY,
            'forced' => false,
        ],
        self::ORDER_PENDING => [
            'identifier' => 'ORDER-PENDING',
            'poNumber' => 'PO-PENDING',
            'currency' => 'USD',
            'subtotal' => '75.00',
            'total' => '85.00',
            'paymentStatus' => PaymentStatuses::PENDING,
            'forced' => false,
        ],
        self::ORDER_PAYMENT_FAILED => [
            'identifier' => 'ORDER-FAILED',
            'poNumber' => 'PO-FAILED',
            'currency' => 'GBP',
            'subtotal' => '150.00',
            'total' => '165.00',
            'paymentStatus' => PaymentStatuses::DECLINED,
            'forced' => false,
        ],
        self::ORDER_CANCELED => [
            'identifier' => 'ORDER-CANCELED',
            'poNumber' => 'PO-CANCELED',
            'currency' => 'USD',
            'subtotal' => '300.00',
            'total' => '330.00',
            'paymentStatus' => PaymentStatuses::CANCELED,
            'forced' => false,
        ],
        self::ORDER_REFUNDED => [
            'identifier' => 'ORDER-REFUNDED',
            'poNumber' => 'PO-REFUNDED',
            'currency' => 'USD',
            'subtotal' => '120.00',
            'total' => '132.00',
            'paymentStatus' => PaymentStatuses::REFUNDED,
            'forced' => false,
        ],
        self::ORDER_AUTHORIZED => [
            'identifier' => 'ORDER-AUTHORIZED',
            'poNumber' => 'PO-AUTHORIZED',
            'currency' => 'GBP',
            'subtotal' => '80.00',
            'total' => '88.00',
            'paymentStatus' => PaymentStatuses::AUTHORIZED,
            'forced' => false,
        ],
        self::ORDER_NO_PAYMENT_STATUS => [
            'identifier' => 'ORDER-NO-STATUS',
            'poNumber' => 'PO-NO-STATUS',
            'currency' => 'USD',
            'subtotal' => '90.00',
            'total' => '99.00',
            'paymentStatus' => null, // No payment status will be set
            'forced' => false,
        ],
        self::ORDER_FORCED_STATUS => [
            'identifier' => 'ORDER-FORCED',
            'poNumber' => 'PO-FORCED',
            'currency' => 'EUR',
            'subtotal' => '250.00',
            'total' => '275.00',
            'paymentStatus' => PaymentStatuses::PAID_IN_FULL,
            'forced' => true, // This status is forced and won't be recalculated
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadUser::class,
            LoadCustomers::class,
            LoadCustomerUserData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var PaymentStatusManager $paymentStatusManager */
        $paymentStatusManager = $this->container->get('oro_payment.manager.payment_status');

        foreach ($this->orders as $name => $orderData) {
            $order = $this->createOrder($manager, $orderData);
            $this->setReference($name, $order);
        }

        // Orders must be persisted before setting payment statuses.
        $manager->flush();

        foreach ($this->orders as $name => $orderData) {
            /** @var Order $order */
            $order = $this->getReference($name);

            // Set payment status if specified
            if ($orderData['paymentStatus'] !== null) {
                $paymentStatusManager->setPaymentStatus(
                    $order,
                    $orderData['paymentStatus'],
                    $orderData['forced']
                );
            }
        }
    }

    private function createOrder(ObjectManager $manager, array $orderData): Order
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        /** @var Customer $customer */
        $customer = $customerUser->getCustomer();

        $website = $this->getDefaultWebsite();

        $order = new Order();
        $order->setIdentifier($orderData['identifier']);
        $order->setPoNumber($orderData['poNumber']);
        $order->setOwner($user);
        $order->setOrganization($organization);
        $order->setCurrency($orderData['currency']);
        $order->setCustomer($customer);
        $order->setCustomerUser($customerUser);
        $order->setWebsite($website);
        $order->setShipUntil(new \DateTime('+7 days'));
        $order->setSubtotal($orderData['subtotal']);
        $order->setTotal($orderData['total']);

        $manager->persist($order);

        return $order;
    }

    private function getDefaultWebsite(): Website
    {
        return $this->container
            ->get('doctrine')
            ->getRepository(Website::class)
            ->findOneBy(['default' => true]);
    }
}
