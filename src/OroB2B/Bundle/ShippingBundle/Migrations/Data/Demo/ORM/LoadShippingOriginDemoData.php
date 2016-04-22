<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Sluggable\Fixture\Issue116\Country;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class LoadShippingOriginDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use UserUtilityTrait;

    /** @var array */
    protected $demoData = [
        [
            'name' => 'Warehouse With ShippingOrigin 1',
            'shipping_origin' => [
                'region_text' => 'Alabama',
                'postal_code' => '36849',
                'country' => 'US',
                'region' => 'US-AL',
                'city' => 'Auburn',
                'street' => 'Street',
                'street2' => 'Street2',
            ]
        ],
        [
            'name' => 'Warehouse With ShippingOrigin 2',
            'shipping_origin' => [
                'region_text' => 'Illinois',
                'postal_code' => '60502',
                'country' => 'US',
                'region' => 'US-IL',
                'city' => 'Aurora',
                'street' => 'Street',
                'street2' => 'Street2',
            ]
        ],
        ['name' => 'Warehouse No ShippingOrigin']
    ];

    /** @var ContainerInterface */
    protected $container;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->doctrineHelper = $this->container->get('oro_entity.doctrine_helper');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\AddressBundle\Migrations\Data\ORM\LoadCountryData',
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();
        foreach ($this->demoData as $row) {
            $warehouse = new Warehouse();
            $warehouse
                ->setName($row['name'])
                ->setOwner($businessUnit)
                ->setOrganization($organization);
            $manager->persist($warehouse);

            if (isset($row['shipping_origin'])) {
                $entity = new ShippingOriginWarehouse($row['shipping_origin']);
                if (!empty($row['shipping_origin']['country'])) {
                    /** @var Country $country */
                    $country = $this->doctrineHelper->getEntityReference(
                        'OroAddressBundle:Country',
                        $row['shipping_origin']['country']
                    );
                    $entity->setCountry($country);
                }
                if (!empty($row['shipping_origin']['region'])) {
                    /** @var Region $region */
                    $region = $this->doctrineHelper->getEntityReference(
                        'OroAddressBundle:Region',
                        $row['shipping_origin']['region']
                    );
                    $entity->setRegion($region);
                }
                $entity->setWarehouse($warehouse);
                $manager->persist($entity);
            }
        }
        $manager->flush();
    }
}
