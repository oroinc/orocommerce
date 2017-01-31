<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class LoadTransports extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getTransportsData() as $reference => $data) {
            $entity = new DPDTransport();
            foreach ($data['applicableShippingServices'] as $shipServiceRef) {
                /** @var ShippingService $shipService */
                $shipService = $this->getReference($shipServiceRef);
                $entity->addApplicableShippingService($shipService);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference', 'applicableShippingServices']);
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
            __NAMESPACE__.'\LoadShippingServices',
        ];
    }

    /**
     * @return array
     */
    protected function getTransportsData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/transports.yml'));
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
