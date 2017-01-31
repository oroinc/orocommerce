<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class LoadRates extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getRatesData() as $reference => $data) {
            $entity = new Rate();

            $transport = $this->getReference($data['transport']);
            $entity->setTransport($transport);

            $shipService = $this->getReference($data['shippingService']);
            $entity->setShippingService($shipService);

            $country = $this->getReference($data['country']);
            $entity->setCountry($country);

            if (array_key_exists('region', $data)) {
                $region = $this->getReference($data['region']);
                $entity->setRegion($region);
            }

            $this->setEntityPropertyValues($entity, $data, ['transport', 'shippingService', 'country', 'region']);
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__.'\LoadTransports',
            __NAMESPACE__.'\LoadShippingCountriesAndRegions',

        ];
    }

    /**
     * @return array
     */
    protected function getRatesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_rates.yml'));
    }

    /**
     * @param object $entity
     * @param array  $data
     * @param array  $excludeProperties
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
