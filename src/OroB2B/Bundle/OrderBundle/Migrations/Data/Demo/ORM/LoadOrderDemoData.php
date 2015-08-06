<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;

class LoadOrderDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $paymentTerms = [];

    /**
     * @var array
     */
    protected $countries = [];

    /**
     * @var array
     */
    protected $regions = [];

    /**
     * @var array
     */
    protected $billingAddressesData = [
        'label' => 'Office Address',
        'country' => 'US',
        'city' => 'Rochester',
        'region' => 'US-NY',
        'street' => '1215 Caldwell Road',
        'postalCode' => '14608'
    ];

    /**
     * @var array
     */
    protected $shippingAddressData = [
        'label' => 'Warehouse Address',
        'country' => 'US',
        'city' => 'Romney',
        'region' => 'US-IN',
        'street' => '2413 Capitol Avenue',
        'postalCode' => '47981'
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData'
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityRepository $userRepository */
        $userRepository = $manager->getRepository('OroUserBundle:User');
        /** @var User $user */
        $user = $userRepository->findOneBy([]);

        $order = new Order();
        $order
            ->setOwner($user)
            ->setAccountUser($this->getAccountUser($manager))
            ->setOrganization($user->getOrganization())
            ->setIdentifier($user->getId())
            ->setBillingAddress($this->createOrderAddress($manager, $this->billingAddressesData))
            ->setShippingAddress($this->createOrderAddress($manager, $this->shippingAddressData))
            ->setPaymentTerm($this->getPaymentTermByLabel($manager, LoadPaymentTermDemoData::PAYMENT_TERM_NET_10))
            ->setShipUntil(new \DateTime())
            ->setCurrency('USD')
            ->setPoNumber('CV032342USDD')
            ->setSubtotal(15535.88);

        $manager->persist($order);
        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param array $address
     * @return OrderAddress
     */
    protected function createOrderAddress(EntityManager $manager, array $address)
    {
        $orderAddress = new OrderAddress();
        $orderAddress
            ->setLabel($address['label'])
            ->setCountry($this->getCountryByIso2Code($manager, $address['country']))
            ->setCity($address['city'])
            ->setRegion($this->getRegionByIso2Code($manager, $address['region']))
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode']);

        $manager->persist($orderAddress);

        return $orderAddress;
    }

    /**
     * @param ObjectManager $manager
     * @param string $label
     * @return PaymentTerm|null
     */
    protected function getPaymentTermByLabel(ObjectManager $manager, $label)
    {
        if (!array_key_exists($label, $this->paymentTerms)) {
            $this->paymentTerms[$label] = $manager->getRepository('OroB2BPaymentBundle:PaymentTerm')
                ->findOneBy(['label' => $label]);
        }

        return $this->paymentTerms[$label];
    }

    /**
     * @param EntityManager $manager
     * @param string $iso2Code
     * @return Country|null
     */
    protected function getCountryByIso2Code(EntityManager $manager, $iso2Code)
    {
        if (!array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getReference('OroAddressBundle:Country', $iso2Code);
        }

        return $this->countries[$iso2Code];
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return Region|null
     */
    protected function getRegionByIso2Code(EntityManager $manager, $code)
    {
        if (!array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getReference('OroAddressBundle:Region', $code);
        }

        return $this->regions[$code];
    }

    /**
     * @param ObjectManager $manager
     * @return AccountUser|null
     */
    protected function getAccountUser(ObjectManager $manager)
    {
        return $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy([]);
    }
}
