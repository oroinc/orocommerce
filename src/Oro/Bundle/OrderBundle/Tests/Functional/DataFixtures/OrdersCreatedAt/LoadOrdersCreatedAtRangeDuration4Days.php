<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Orders created in date range 2023-01-01 - 2023-01-04
 */
class LoadOrdersCreatedAtRangeDuration4Days extends LoadOrders
{
    public const SUB_ORDER_1_OF_ORDER_1 = 'sub_order_1_of_simple_order';
    public const CANCELLED_ORDER_1 = 'cancelled_order_1';

    /** @var array */
    protected $orders = [
        self::ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => '1234567890',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-01 10:00:00',
        ],
        self::ORDER_2 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO2',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-01 13:00:00',
        ],
        self::ORDER_3 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-02 10:00:00',
        ],
        self::ORDER_4 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-02 14:00:00',
        ],
        self::ORDER_5 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-03 10:00:00',
        ],
        self::ORDER_6 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO6',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-04 10:00:00',
        ],
        self::SUB_ORDER_1_OF_ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => TestCustomerUserData::AUTH_USER,
            'poNumber' => 'PO_SUB1',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'parentOrder' => self::ORDER_1,
            'createdAt' => '2023-01-04 10:00:00',
        ],
        self::CANCELLED_ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => TestCustomerUserData::AUTH_USER,
            'poNumber' => 'PO_SUB1',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'internalStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            'createdAt' => '2023-01-04 10:00:00',
        ],
    ];

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $orderData
     * @return Order
     */
    protected function createOrder(ObjectManager $manager, $name, array $orderData)
    {
        $orderMetadata = $manager->getClassMetadata(Order::class);
        $this->disablePrePersistCallback($orderMetadata);

        /** @var User $user */
        $user = $this->getReference($orderData['user']);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->getReference('organization'));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => $orderData['customerUser']]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference($orderData['paymentTerm']);

        $website = $this->getDefaultWebsite();
        $this->setReference('defaultWebsite', $website);

        $createdAt = new \DateTime($orderData['createdAt'] ?? 'now', new \DateTimeZone('UTC'));

        $order = new Order();
        $order
            ->setIdentifier($name)
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
            ->setShipUntil(new \DateTime())
            ->setCurrency($orderData['currency'])
            ->setPoNumber($orderData['poNumber'])
            ->setSubtotal($orderData['subtotal'])
            ->setTotal($orderData['total'])
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($website)
            ->setCustomerUser($customerUser)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt);

        if (isset($orderData['parentOrder'])) {
            $order->setParent($this->getReference($orderData['parentOrder']));
        }

        if (isset($orderData['internalStatus'])) {
            /** @var AbstractEnumValue $internalStatus */
            $internalStatus = $manager
                ->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
                ->find($orderData['internalStatus']);

            $order->setInternalStatus($internalStatus);
        }

        $this->container->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        if (array_key_exists('shippingMethod', $orderData)) {
            $order->setShippingMethod($orderData['shippingMethod']);
        }
        if (array_key_exists('shippingMethodType', $orderData)) {
            $order->setShippingMethodType($orderData['shippingMethodType']);
        }
        if (array_key_exists('shippingCostAmount', $orderData)) {
            $order->setEstimatedShippingCostAmount($orderData['shippingCostAmount']);
        }

        $manager->persist($order);
        $this->addReference($name, $order);

        $this->enablePrePersistCallback($orderMetadata);

        return $order;
    }

    private function disablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff($lifecycleCallbacks['prePersist'], ['prePersist']);
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function enablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }
}
