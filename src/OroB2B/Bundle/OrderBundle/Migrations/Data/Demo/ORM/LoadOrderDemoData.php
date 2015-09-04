<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class LoadOrderDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var array */
    protected $countries = [];

    /** @var array */
    protected $regions = [];

    /** @var array */
    protected $paymentTerms = [];

    /** @var array */
    protected $priceLists = [];

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
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData'
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BOrderBundle/Migrations/Data/Demo/ORM/data/orders.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        /** @var EntityRepository $userRepository */
        $userRepository = $manager->getRepository('OroUserBundle:User');

        /** @var User $user */
        $user = $userRepository->findOneBy([]);

        $accountUser = $this->getAccountUser($manager);

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $order = new Order();

            $billingAddress = [
                'label' => $row['billingAddressLabel'],
                'country' => $row['billingAddressCountry'],
                'city' => $row['billingAddressCity'],
                'region' => $row['billingAddressRegion'],
                'street' => $row['billingAddressStreet'],
                'postalCode' => $row['billingAddressPostalCode']
            ];

            $shippingAddress = [
                'label' => $row['shippingAddressLabel'],
                'country' => $row['shippingAddressCountry'],
                'city' => $row['shippingAddressCity'],
                'region' => $row['shippingAddressRegion'],
                'street' => $row['shippingAddressStreet'],
                'postalCode' => $row['shippingAddressPostalCode']
            ];

            $order
                ->setOwner($user)
                ->setAccount($accountUser->getAccount())
                ->setIdentifier($row['identifier'])
                ->setAccountUser($accountUser)
                ->setOrganization($user->getOrganization())
                ->setBillingAddress($this->createOrderAddress($manager, $billingAddress))
                ->setShippingAddress($this->createOrderAddress($manager, $shippingAddress))
                ->setPaymentTerm($this->getPaymentTerm($manager, $row['paymentTerm']))
                ->setPriceList($this->getPriceList($manager, $row['priceListName']))
                ->setShipUntil(new \DateTime())
                ->setCurrency($row['currency'])
                ->setPoNumber($row['poNumber'])
                ->setSubtotal($row['subtotal']);

            if (!empty($row['customerNotes'])) {
                $order->setCustomerNotes($row['customerNotes']);
            }

            $manager->persist($order);
        }

        fclose($handler);

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
     * @return AccountUser|null
     */
    protected function getAccountUser(ObjectManager $manager)
    {
        return $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy([]);
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
     * @param EntityManager $manager
     * @param string $label
     * @return PaymentTerm
     */
    protected function getPaymentTerm(EntityManager $manager, $label)
    {
        if (!array_key_exists($label, $this->paymentTerms)) {
            $this->paymentTerms[$label] = $manager->getRepository('OroB2BPaymentBundle:PaymentTerm')
                ->findOneBy(['label' => $label]);
        }

        return $this->paymentTerms[$label];
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return PriceList
     */
    protected function getPriceList(EntityManager $manager, $name)
    {
        if (!array_key_exists($name, $this->priceLists)) {
            $this->priceLists[$name] = $manager->getRepository('OroB2BPricingBundle:PriceList')
                ->findOneBy(['name' => $name]);
        }

        return $this->priceLists[$name];
    }
}
