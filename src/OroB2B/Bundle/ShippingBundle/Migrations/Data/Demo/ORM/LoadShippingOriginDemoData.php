<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM\LoadWarehouseDemoData;

class LoadShippingOriginDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $data = [
        [
            'warehouse' => LoadWarehouseDemoData::MAIN_WAREHOUSE,
            'postalCode' => '36832',
            'country' => 'US',
            'region' => 'US-AL',
            'city' => 'Auburn',
            'street' => '570 Devall Dr # 101'
        ],
        [
            'warehouse' => LoadWarehouseDemoData::ADDITIONAL_WAREHOUSE,
            'postalCode' => '60505',
            'country' => 'US',
            'region' => 'US-IL',
            'city' => 'Aurora',
            'street' => '1570 Molitor Rd',
        ]
    ];

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
            'Oro\Bundle\AddressBundle\Migrations\Data\ORM\LoadCountryData',
            'OroB2B\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM\LoadWarehouseDemoData'
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $row) {
            $entity = new ShippingOriginWarehouse();
            $entity->setWarehouse($this->getReference($row['warehouse']))
                ->setCountry($manager->getReference('OroAddressBundle:Country', $row['country']))
                ->setRegion($manager->getReference('OroAddressBundle:Region', $row['region']))
                ->setPostalCode($row['postalCode'])
                ->setCity($row['city'])
                ->setStreet($row['street']);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
