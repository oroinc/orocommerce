<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Symfony\Component\Yaml\Yaml;

class LoadShippingMethodTypeConfigsWithFakeTypes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadShippingMethodConfigsWithFakeMethods::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingMethodTypeConfigsData() as $reference => $data) {
            $this->loadShippingMethodTypeConfig($reference, $data, $manager);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingMethodTypeConfigsData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_method_type_configs_with_fake_types.yml'));
    }

    /**
     * @param string        $reference
     * @param array         $data
     * @param ObjectManager $manager
     */
    private function loadShippingMethodTypeConfig($reference, $data, ObjectManager $manager)
    {
        $methodConfig = $this->getShippingMethodConfig($data['method_config']);

        $typeConfig = $this->createMethodTypeConfig($methodConfig, $data['type']);

        $manager->persist($typeConfig);

        $this->setReference($reference, $typeConfig);
    }

    /**
     * @param ShippingMethodConfig $methodConfig
     * @param string               $type
     *
     * @return ShippingMethodTypeConfig
     */
    private function createMethodTypeConfig(ShippingMethodConfig $methodConfig, $type)
    {
        $configRule = new ShippingMethodTypeConfig();

        return $configRule->setMethodConfig($methodConfig)
            ->setType($type);
    }

    /**
     * @param string $reference
     *
     * @return ShippingMethodConfig|object
     */
    private function getShippingMethodConfig($reference)
    {
        return $this->getReference($reference);
    }
}
