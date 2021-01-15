<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\Yaml\Yaml;

class LoadShippingMethodConfigsWithFakeMethods extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadShippingMethodsConfigsRules::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingMethodConfigsData() as $reference => $data) {
            $this->loadShippingMethodConfig($reference, $data, $manager);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingMethodConfigsData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_method_configs_with_fake_methods.yml'));
    }

    /**
     * @param string        $reference
     * @param array         $data
     * @param ObjectManager $manager
     */
    private function loadShippingMethodConfig($reference, $data, ObjectManager $manager)
    {
        $methodsConfigsRule = $this->getShippingMethodsConfigsRule($data['methods_configs_rule']);

        $methodConfig = $this->createMethodConfig($methodsConfigsRule, $data['method']);

        $manager->persist($methodConfig);

        $this->setReference($reference, $methodConfig);
    }

    /**
     * @param ShippingMethodsConfigsRule $configsRule
     * @param string                     $method
     *
     * @return ShippingMethodConfig
     */
    private function createMethodConfig(ShippingMethodsConfigsRule $configsRule, $method)
    {
        $methodConfig = new ShippingMethodConfig();

        return $methodConfig->setMethodConfigsRule($configsRule)
            ->setMethod($method);
    }

    /**
     * @param string $reference
     *
     * @return ShippingMethodsConfigsRule|object
     */
    private function getShippingMethodsConfigsRule($reference)
    {
        return $this->getReference($reference);
    }
}
