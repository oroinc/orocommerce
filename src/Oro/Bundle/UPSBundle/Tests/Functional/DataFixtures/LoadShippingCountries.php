<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;

use Symfony\Component\Yaml\Yaml;

class LoadShippingCountries extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingCountriesData() as $reference => $data) {
            $entity = new Country($data['iso2']);
            $entity
                ->setIso3Code($data['iso3'])
                ->setName($reference);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingCountriesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_countries.yml'));
    }
}
