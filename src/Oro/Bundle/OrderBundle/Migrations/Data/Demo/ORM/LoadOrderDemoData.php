<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM\Trait\OrderLineItemsDemoDataTrait;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData;
use Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM\LoadTaxConfigurationDemoData;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loading order demo data.
 */
class LoadOrderDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use OrderLineItemsDemoDataTrait;

    /** @var array */
    protected $countries = [];

    /** @var array */
    protected $regions = [];

    /** @var array */
    protected $paymentTerms = [];

    /** @var array */
    protected $websites = [];

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
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
            LoadCustomerDemoData::class,
            LoadCustomerUserDemoData::class,
            LoadPaymentTermDemoData::class,
            LoadPriceListDemoData::class,
            LoadShoppingListDemoData::class,
            LoadTaxConfigurationDemoData::class,
            LoadProductDemoData::class,
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroOrderBundle/Migrations/Data/Demo/ORM/data/orders.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $paymentTermAccessor = $this->container->get('oro_payment_term.provider.payment_term_association');

        /** @var ShoppingList $shoppingList */
        $shoppingList = $manager->getRepository('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList')->findOneBy([]);

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        /** @var EntityRepository $userRepository */
        $userRepository = $manager->getRepository('OroUserBundle:User');

        /** @var User $user */
        $user = $userRepository->findOneBy([]);

        $rateConverter = $this->container->get('oro_currency.converter.rate');

        $regularCustomerUser = $this->getCustomerUser($manager);
        $guestCustomerUser = $this->getCustomerUser($manager, true);

        $orderMetadata = $manager->getClassMetadata(Order::class);
        $this->disablePrePersistCallback($orderMetadata);

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $customerUser = $row['isGuest'] ? $guestCustomerUser : $regularCustomerUser;
            $order = new Order();

            $billingAddress = $this->getBillingAddressData($row, $customerUser);
            $shippingAddress = $this->getShippingAddressData($row, $customerUser);

            $total = MultiCurrency::create($row['total'], $row['currency']);
            $baseTotal = $rateConverter->getBaseCurrencyAmount($total);
            $total->setBaseCurrencyValue($baseTotal);

            $subtotal = MultiCurrency::create($row['subtotal'], $row['currency']);
            $baseSubtotal = $rateConverter->getBaseCurrencyAmount($subtotal);
            $subtotal->setBaseCurrencyValue($baseSubtotal);

            $randomDateTime = $this->getRandomDateTime();

            $order
                ->setOwner($user)
                ->setCustomer($customerUser->getCustomer())
                ->setIdentifier($row['identifier'])
                ->setCustomerUser($customerUser)
                ->setOrganization($user->getOrganization())
                ->setBillingAddress($this->createOrderAddress($manager, $billingAddress))
                ->setShippingAddress($this->createOrderAddress($manager, $shippingAddress))
                ->setWebsite($this->getWebsite($manager, $row['websiteName']))
                ->setShipUntil(new \DateTime())
                ->setCurrency($row['currency'])
                ->setPoNumber($row['poNumber'])
                ->setTotalObject($total)
                ->addLineItem($this->getOrderLineItem($manager))
                ->setSubtotalObject($subtotal)
                ->setInternalStatus($this->getOrderInternalStatusByName($row['internalStatus'], $manager))
                ->setCreatedAt($randomDateTime)
                ->setUpdatedAt($randomDateTime);

            $paymentTermAccessor->setPaymentTerm($order, $this->getPaymentTerm($manager, $row['paymentTerm']));

            if ($row['sourceEntityClass'] === 'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList') {
                $order->setSourceEntityClass($row['sourceEntityClass']);
                $order->setSourceEntityId($shoppingList->getId());
            }

            if (!empty($row['customerNotes'])) {
                $order->setCustomerNotes($row['customerNotes']);
            }

            $manager->persist($order);
        }

        fclose($handler);

        $this->enablePrePersistCallback($orderMetadata);

        $manager->flush();
    }

    private function getBillingAddressData(array $row, CustomerUser $customerUser): array
    {
        return [
            'label' => $row['billingAddressLabel'],
            'country' => $row['billingAddressCountry'],
            'city' => $row['billingAddressCity'],
            'region' => $row['billingAddressRegion'],
            'street' => $row['billingAddressStreet'],
            'postalCode' => $row['billingAddressPostalCode'],
            'firstName' => $customerUser->getFirstName(),
            'lastName' => $customerUser->getLastName(),
        ];
    }

    private function getShippingAddressData(array $row, CustomerUser $customerUser): array
    {
        return [
            'label' => $row['shippingAddressLabel'],
            'country' => $row['shippingAddressCountry'],
            'city' => $row['shippingAddressCity'],
            'region' => $row['shippingAddressRegion'],
            'street' => $row['shippingAddressStreet'],
            'postalCode' => $row['shippingAddressPostalCode'],
            'firstName' => $customerUser->getFirstName(),
            'lastName' => $customerUser->getLastName(),
        ];
    }

    protected function createOrderAddress(EntityManagerInterface $manager, array $address): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress
            ->setLabel($address['label'])
            ->setCountry($this->getCountryByIso2Code($manager, $address['country']))
            ->setCity($address['city'])
            ->setRegion($this->getRegionByIso2Code($manager, $address['region']))
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode'])
            ->setFirstName($address['firstName'])
            ->setLastName($address['lastName'])
            ->setPhone('1234567890');

        $manager->persist($orderAddress);

        return $orderAddress;
    }

    /**
     * @param string $name
     * @param ObjectManager $manager
     *
     * @return AbstractEnumValue|null|object
     */
    protected function getOrderInternalStatusByName($name, ObjectManager $manager)
    {
        return $manager
            ->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->findOneBy(['id' => $name,]);
    }

    /**
     * @param bool $isGuest
     * @return null|CustomerUser
     */
    protected function getCustomerUser(ObjectManager $manager, $isGuest = false)
    {
        return $manager->getRepository('OroCustomerBundle:CustomerUser')->findOneBy(['isGuest' => $isGuest]);
    }

    protected function getCountryByIso2Code(EntityManagerInterface $manager, string $iso2Code): ?Country
    {
        if (!array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getReference('OroAddressBundle:Country', $iso2Code);
        }

        return $this->countries[$iso2Code];
    }

    protected function getRegionByIso2Code(EntityManagerInterface $manager, string $code): ?Region
    {
        if (!array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getReference('OroAddressBundle:Region', $code);
        }

        return $this->regions[$code];
    }

    protected function getPaymentTerm(EntityManagerInterface $manager, string $label): PaymentTerm
    {
        if (!array_key_exists($label, $this->paymentTerms)) {
            $this->paymentTerms[$label] = $manager->getRepository('OroPaymentTermBundle:PaymentTerm')
                ->findOneBy(['label' => $label]);
        }

        return $this->paymentTerms[$label];
    }

    protected function getWebsite(EntityManagerInterface $manager, string $name): Website
    {
        if (!array_key_exists($name, $this->websites)) {
            $this->websites[$name] = $manager->getRepository('OroWebsiteBundle:Website')
                ->findOneBy(['name' => $name]);
        }

        return $this->websites[$name];
    }

    private function enablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function disablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff($lifecycleCallbacks['prePersist'], ['prePersist']);
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function getRandomDateTime(): \DateTime
    {
        return new \DateTime(
            sprintf(
                '-%sday %s:%s',
                random_int(0, 7),
                random_int(0, 23),
                random_int(0, 59)
            ),
            new \DateTimeZone('UTC')
        );
    }
}
