<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServices extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingServicesData() as $reference => $data) {
            $entity = new ShippingService();
            $entity
                ->setCode($data['code'])
                ->setDescription($data['description'])
                ->setExpressService($data['express']);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingServicesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_services.yml'));
    }
}
