<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
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
use Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData;
use Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM\LoadTaxConfigurationDemoData;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loading customer order demo data.
 */
class LoadCustomerOrderDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;
    use OrderLineItemsDemoDataTrait;

    /** @var array */
    private $countries = [];

    /** @var array */
    private $regions = [];

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
            LoadOrderLineItemDemoData::class,
        ];
    }

    /**
     * @param EntityManagerInterface $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CustomerUser[] $customerUsers */
        $customerUsers = $manager->getRepository(CustomerUser::class)->findAll();

        /** @var User $user */
        $defaultUser = $manager->getRepository(User::class)->findOneBy([]);

        /** @var AbstractEnumValue[] $internalStatuses */
        $internalStatuses = $manager
            ->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->findAll();

        $paymentTerm = $manager->getRepository(PaymentTerm::class)->findOneBy([]);
        $paymentTermAccessor = $this->container->get('oro_payment_term.provider.payment_term_association');
        $website = $this->getWebsite($manager);

        $orderMetadata = $manager->getClassMetadata(Order::class);
        $this->disablePrePersistCallback($orderMetadata);

        $index = 0;
        $timeZone = new \DateTimeZone('UTC');
        foreach ($customerUsers as $customerUser) {
            /** @var User $user */
            $user = $customerUser->getOwner() ?: $defaultUser;

            foreach ($internalStatuses as $internalStatus) {
                $order = new Order();
                $orderAddress = $this->getOrderAddressByCustomer($customerUser, $manager);
                $randomDateTime = $this->getRandomDateTime();

                $order
                    ->setInternalStatus($internalStatus)
                    ->setOwner($user)
                    ->setPoNumber(sprintf('POSD%03d%03d', $customerUser->getId(), $index))
                    ->setIdentifier(sprintf('COI%03d%03d', $customerUser->getId(), $index))
                    ->setCustomer($customerUser->getCustomer())
                    ->setCustomerUser($customerUser)
                    ->setOrganization($user->getOrganization())
                    ->setBillingAddress($orderAddress)
                    ->setShippingAddress($orderAddress)
                    ->setWebsite($website)
                    ->addLineItem($this->getOrderLineItem($manager))
                    ->setCurrency(CurrencyConfiguration::DEFAULT_CURRENCY)
                    ->setShipUntil(new \DateTime(sprintf('+%d hours', random_int(0, 100)), $timeZone))
                    ->setCreatedAt($randomDateTime)
                    ->setUpdatedAt($randomDateTime);

                $paymentTermAccessor->setPaymentTerm($order, $paymentTerm);

                $manager->persist($order);

                $index++;
            }
        }

        $this->enablePrePersistCallback($orderMetadata);

        $manager->flush();
    }

    /**
     * @param CustomerUser $customerUser
     * @param EntityManagerInterface $manager
     *
     * @return OrderAddress
     */
    private function getOrderAddressByCustomer(CustomerUser $customerUser, EntityManagerInterface $manager)
    {
        $customerAddresses = $customerUser->getAddresses();
        $customerAddress = $customerAddresses->first();

        $orderAddress = new OrderAddress();

        if (!$customerAddress) {
            $orderAddress
                ->setLabel(uniqid('Label ', true))
                ->setCountry($this->getCountryByIso2Code($manager, 'US'))
                ->setCity('Aurora')
                ->setRegion($this->getRegionByIso2Code($manager, 'US-IL'))
                ->setStreet('Address')
                ->setPostalCode(sprintf('%d', random_int(60000, 65000)));
        } else {
            $orderAddress
                ->setLabel($customerAddress->getLabel())
                ->setCountry($customerAddress->getCountry())
                ->setCity($customerAddress->getCity())
                ->setRegion($customerAddress->getRegion())
                ->setStreet($customerAddress->getStreet())
                ->setPostalCode($customerAddress->getPostalCode());
        }
        $orderAddress->setFirstName($customerUser->getFirstName())
            ->setLastName($customerUser->getLastName())
            ->setPhone('1234567890');

        $manager->persist($orderAddress);

        return $orderAddress;
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string $name
     *
     * @return Website
     */
    private function getWebsite(EntityManagerInterface $manager, $name = LoadWebsiteData::DEFAULT_WEBSITE_NAME)
    {
        return $manager->getRepository(Website::class)->findOneBy(['name' => $name]);
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string $iso2Code
     *
     * @return Country|null
     */
    private function getCountryByIso2Code(EntityManagerInterface $manager, $iso2Code)
    {
        if (!array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getReference(Country::class, $iso2Code);
        }

        return $this->countries[$iso2Code];
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string $code
     *
     * @return Region|null
     */
    private function getRegionByIso2Code(EntityManagerInterface $manager, $code)
    {
        if (!array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getReference(Region::class, $code);
        }

        return $this->regions[$code];
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
