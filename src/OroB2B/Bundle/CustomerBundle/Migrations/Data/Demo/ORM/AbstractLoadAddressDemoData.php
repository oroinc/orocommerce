<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;

abstract class AbstractLoadAddressDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ObjectRepository|EntityRepository */
    protected $countryRepository;

    /** @var ObjectRepository|EntityRepository */
    protected $regionRepository;
    
    /** @var ObjectRepository|EntityRepository */
    protected $addressTypeRepository;

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
    public function load(ObjectManager $manager)
    {
        $this->initRepositories();
    }

    protected function createAddress($data)
    {
        /** @var Country $country */
        $country = $this->countryRepository->findOneBy(['iso2Code' => $data['country']]);
        if (!$country) {
            throw new \RuntimeException('Can\'t find country with ISO ' . $data['country']);
        }

        /** @var Region $region */
        $region = $this->regionRepository->findOneBy(['country' => $country, 'code' => $data['state']]);
        if (!$region) {
            throw new \RuntimeException(
                printf('Can\'t find region with country ISO %s and code %s', $data['country'], $data['state'])
            );
        }

        $types = [];
        $typesFromData = explode(',', $data['types']);
        foreach ($typesFromData as $type) {
            $types[] = $this->addressTypeRepository->find($type);
        }

        $defaultTypes = [];
        $defaultTypesFromData = explode(',', $data['defaultTypes']);
        foreach ($defaultTypesFromData as $defaultType) {
            $defaultTypes[] = $this->addressTypeRepository->find($defaultType);
        }

        $address = $this->getNewAddressEntity();
        $address
            ->setPrimary(true)
            ->setLabel('Primary address')
            ->setCountry($country)
            ->setStreet($data['street'])
            ->setCity($data['city'])
            ->setRegion($region)
            ->setPostalCode($data['zipCode'])
            ->setTypes(new ArrayCollection($types))
            ->setDefaults(new ArrayCollection($defaultTypes));

        return $address;
    }

    protected function initRepositories()
    {
        $doctrine = $this->container->get('doctrine');
        $this->countryRepository = $doctrine
            ->getManagerForClass('OroAddressBundle:Country')
            ->getRepository('OroAddressBundle:Country');

        $this->regionRepository = $doctrine
            ->getManagerForClass('OroAddressBundle:Region')
            ->getRepository('OroAddressBundle:Region');

        $this->addressTypeRepository = $doctrine
            ->getManagerForClass('OroAddressBundle:AddressType')
            ->getRepository('OroAddressBundle:AddressType');
    }

    /**
     * Return new entity compatible with AbstractDefaultTypedAddress
     *
     * @return AbstractDefaultTypedAddress
     */
    abstract protected function getNewAddressEntity();
}
