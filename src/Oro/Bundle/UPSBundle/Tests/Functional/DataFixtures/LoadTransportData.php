<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class LoadTransportData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array Transports configuration
     */
    protected $transportData = [
        [
            'reference' => 'ups:transport_1',
            'upsApiUser' => 'user_1',
            'upsApiPassword' => 'password_1',
            'upsApiKey' => 'key_1',
            'upsShippingAccountNumber' => 'ship_customer_number_1',
            'upsShippingAccountName' => 'ship_customer_name_1',
            'upsCountry' => 'ups.shipping_country.1',
            'applicableShippingServices' => [
                'ups.shipping_service.1'
            ]
        ],
        [
            'reference' => 'ups:transport_2',
            'upsApiUser' => 'user_2',
            'upsApiPassword' => 'password_2',
            'upsApiKey' => 'key_2',
            'upsShippingAccountNumber' => 'ship_customer_number_2',
            'upsShippingAccountName' => 'ship_customer_name_2',
            'upsCountry' => 'ups.shipping_country.1',
            'applicableShippingServices' => [
                'ups.shipping_service.1',
                'ups.shipping_service.2'
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->transportData as $data) {
            $entity = new UPSTransport();
            $country = $this->getReference($data['upsCountry']);
            $entity->setUpsCountry($country);
            foreach ($data['applicableShippingServices'] as $shipServiceRef) {
                /** @var ShippingService $shipService */
                $shipService = $this->getReference($shipServiceRef);
                $entity->addApplicableShippingService($shipService);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference', 'upsCountry', 'applicableShippingServices']);
            $manager->persist($entity);
            $this->setReference($data['reference'], $entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadShippingServices'
        ];
    }

    /**
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = [])
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties, true)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }
}
