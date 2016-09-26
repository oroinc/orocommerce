<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadTransportData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array Transports configuration
     */
    protected $transportData = [
        [
            'reference' => 'ups:transport_1',
            'baseUrl' => 'url_1',
            'apiUser' => 'user_1',
            'apiPassword' => 'password_1',
            'apiKey' => 'key_1',
            'shippingAccountNumber' => 'ship_account_number_1',
            'shippingAccountName' => 'ship_account_name_1',
            'country' => 'ups.shipping_country.1',
            'applicableShippingServices' => [
                'ups.shipping_service.1'
            ]
        ],
        [
            'reference' => 'ups:transport_2',
            'baseUrl' => 'url_2',
            'apiUser' => 'user_2',
            'apiPassword' => 'password_2',
            'apiKey' => 'key_2',
            'shippingAccountNumber' => 'ship_account_number_2',
            'shippingAccountName' => 'ship_account_name_2',
            'country' => 'ups.shipping_country.1',
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
            $country = $this->getReference($data['country']);
            $entity->setCountry($country);
            foreach ($data['applicableShippingServices'] as $shipServiceRef) {
                /** @var ShippingService $shipService */
                $shipService = $this->getReference($shipServiceRef);
                $entity->addApplicableShippingService($shipService);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference', 'country', 'applicableShippingServices']);
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
