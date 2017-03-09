<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Migrations\Data\ORM\AbstractShippingServiceFixture;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServices extends AbstractShippingServiceFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addUpdateShippingServices($manager, $this->getShippingServicesData(), true);
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
