<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOrders extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ORDER_1 = 'simple_order';
    const ORDER_2 = 'simple_order2';
    const ORDER_3 = 'simple_order3';
    const ORDER_4 = 'simple_order4';
    const ORDER_5 = 'simple_order5';
    const ORDER_6 = 'simple_order6';
    const MY_ORDER = 'my_order';
    const ACCOUNT_USER = 'grzegorz.brzeczyszczykiewicz@example.com';
    const SUBTOTAL = '789.0000';
    const TOTAL = '1234.0000';

    /**
     * @var array
     */
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
            'shippingStatus' => 'not_shipped',
        ],
        self::ORDER_2 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'createdBy' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO2',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
        ],
        self::ORDER_3 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'createdBy' => LoadOrderUsers::ORDER_USER_2,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'status' => 'open',
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
            'status' => 'wait_for_approval',
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
            'external' => false,
            'internalStatus' => 'closed'
        ],
        self::ORDER_6 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'createdBy' => LoadOrderUsers::ORDER_USER_2,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO6',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'external' => true
        ],
        self::MY_ORDER => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => TestCustomerUserData::AUTH_USER,
            'poNumber' => 'PO_NUM',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'EUR',
            'subtotal' => '1500.0000',
            'total' => '1700.0000',
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'shippingMethod' => 'flat_rate',
            'shippingMethodType' => 'primary',
            'shippingCostAmount' => '10.0000',
        ],
    ];

    private ?Website $defaultWebsite = null;

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadCustomerUserData::class,
            LoadOrderUsers::class,
            LoadOrderStatuses::class,
            LoadPaymentTermData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->orders as $name => $order) {
            $this->createOrder($manager, $name, $order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $orderData
     * @return Order
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function createOrder(ObjectManager $manager, $name, array $orderData)
    {
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

        $order = new Order();
        $order->setIdentifier($name);
        $order->setOwner($user);
        $order->setOrganization($user->getOrganization());
        $order->setShipUntil(new \DateTime());
        $order->setCurrency($orderData['currency']);
        $order->setPoNumber($orderData['poNumber']);
        $order->setSubtotalDiscountObject(
            MultiCurrency::create($orderData['subtotal'], $orderData['currency'], $orderData['subtotal'])
        );
        $order->setSubtotalObject(
            MultiCurrency::create($orderData['subtotal'], $orderData['currency'], $orderData['subtotal'])
        );
        $order->setTotalObject(
            MultiCurrency::create($orderData['total'], $orderData['currency'], $orderData['total'])
        );
        $order->setCustomer($customerUser->getCustomer());
        $order->setWebsite($website);
        $order->setCustomerUser($customerUser);
        if (isset($orderData['external'])) {
            $order->setExternal($orderData['external']);
        }

        if (isset($orderData['createdBy'])) {
            /** @var User $createdByUser */
            $createdByUser = $this->getReference($orderData['createdBy']);
            if (!$createdByUser->getOrganization()) {
                $createdByUser->setOrganization($this->getReference('organization'));
            }

            $order->setCreatedBy($createdByUser);
        }

        if (isset($orderData['parentOrder'])) {
            $order->setParent($this->getReference($orderData['parentOrder']));
        }

        if (isset($orderData['internalStatus'])) {
            $order->setInternalStatus(
                $manager->getRepository(EnumOption::class)
                    ->find(ExtendHelper::buildEnumOptionId(Order::INTERNAL_STATUS_CODE, $orderData['internalStatus']))
            );
        }
        if (isset($orderData['status'])) {
            $order->setStatus(
                $manager->getRepository(EnumOption::class)
                    ->find(ExtendHelper::buildEnumOptionId(Order::STATUS_CODE, $orderData['status']))
            );
        }
        if (isset($orderData['shippingStatus'])) {
            $order->setShippingStatus(
                $manager->getRepository(EnumOption::class)
                    ->find(ExtendHelper::buildEnumOptionId(Order::SHIPPING_STATUS_CODE, $orderData['shippingStatus']))
            );
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

        return $order;
    }

    protected function getDefaultWebsite(): Website
    {
        if (null === $this->defaultWebsite) {
            $this->defaultWebsite = $this->container->get('doctrine')
                ->getRepository(Website::class)
                ->findOneBy(['default' => true]);
        }

        return $this->defaultWebsite;
    }
}
