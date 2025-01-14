<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractAddressesFixture;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderAddressUpdateFixture extends AbstractAddressesFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    const ORDER_NAME = 'order_with_addresses';
    const ACCOUNT_USER = 'grzegorz.brzeczyszczykiewicz@example.com';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Website
     */
    protected $defaultWebsite;

    protected array $addresses = [
        [
            'customer_user' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'label' => 'grzegorz.brzeczyszczykiewicz@example.com.user_address',
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => true, 'shipping' => true]
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadOrderUsers::class,
            LoadPaymentTermData::class,
            LoadProductUnitPrecisions::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->createOrder($manager);
        $this->createOrderLineItem($manager);
        $this->createCustomerUserAddress($manager);
    }

    private function createOrder(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->getReference('organization'));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => self::ACCOUNT_USER]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference(LoadPaymentTermData::PAYMENT_TERM_NET_10);

        $website = $this->getDefaultWebsite();
        $this->setReference('defaultWebsite', $website);

        $order = new Order();
        $order->setIdentifier(self::ORDER_NAME);
        $order->setOwner($user);
        $order->setOrganization($user->getOrganization());
        $order->setShipUntil(new \DateTime());
        $order->setCurrency('USD');
        $order->setPoNumber('1234567');
        $order->setSubtotal('789.0000');
        $order->setTotal('1234.0000');
        $order->setCustomer($customerUser->getCustomer());
        $order->setWebsite($website);
        $order->setCustomerUser($customerUser);
        $order->setShippingAddress($this->createOrderAddress(
            $manager,
            'shippingAddress',
            $this->getReference('customer.level_1.address_1')
        ));
        $order->setBillingAddress($this->createOrderAddress(
            $manager,
            'billingAddress',
            $this->getReference('customer.level_1.address_2')
        ));
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
                $manager->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
                    ->find($orderData['internalStatus'])
            );
        }

        $this->container->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        $manager->persist($order);
        $this->addReference(self::ORDER_NAME, $order);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    public function createOrderLineItem(ObjectManager $manager)
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($this->getReference(LoadProductData::PRODUCT_1))
            ->setQuantity(10)
            ->setProductUnit($this->getReference(LoadProductUnits::LITER))
            ->setPrice(Price::create(100, 'USD'));

        /* @var Order $order */
        $order = $this->getReference(self::ORDER_NAME);
        $order->addLineItem($lineItem);

        $this->addReference('line_item', $lineItem);

        $manager->persist($lineItem);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param CustomerAddress $customerAddress
     * @return OrderAddress
     */
    private function createOrderAddress(ObjectManager $manager, string $name, CustomerAddress $customerAddress)
    {
        $orderAddress = new OrderAddress();
        $orderAddress
            ->setCountry($customerAddress->getCountry())
            ->setCity($customerAddress->getCity())
            ->setRegion($customerAddress->getRegion())
            ->setStreet($customerAddress->getStreet())
            ->setPostalCode($customerAddress->getPostalCode())
            ->setFirstName(LoadCustomerUserData::FIRST_NAME)
            ->setLastName(LoadCustomerUserData::LAST_NAME)
            ->setCustomerAddress($customerAddress);

        $manager->persist($orderAddress);
        $this->addReference($name, $orderAddress);

        return $orderAddress;
    }

    private function createCustomerUserAddress(ObjectManager $manager)
    {
        foreach ($this->addresses as $addressData) {
            $address = new CustomerUserAddress();
            $address->setSystemOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $address->setFrontendOwner($this->getReference($addressData['customer_user']));
            $this->addAddress($manager, $addressData, $address);
        }
        $manager->flush();
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        if (!$this->defaultWebsite) {
            $this->defaultWebsite = $this->container->get('doctrine')
                ->getRepository(Website::class)
                ->findOneBy(['default' => true]);
        }

        return $this->defaultWebsite;
    }
}
