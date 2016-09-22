<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadShippingRules extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadChannelData'
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getShippingRuleData() as $reference => $data) {
            $entity = new ShippingRule();
            $entity
                ->setName($reference)
                ->setEnabled($data['enabled'])
                ->setPriority($data['priority'])
                ->setConditions($data['conditions'])
                ->setCurrency($data['currency']);

            if (!array_key_exists('destinations', $data)) {
                $data['destinations'] = [];
            }

            foreach ($data['destinations'] as $destination) {
                /** @var Country $country */
                $country = $manager
                    ->getRepository('OroAddressBundle:Country')
                    ->findOneBy(['iso2Code' => $destination['country']]);

                $shippingRuleDestination = new ShippingRuleDestination();
                $shippingRuleDestination
                    ->setRule($entity)
                    ->setCountry($country);

                if (array_key_exists('region', $destination)) {
                    /** @var Region $region */
                    $region = $manager
                        ->getRepository('OroAddressBundle:Region')
                        ->findOneBy(['combinedCode' => $destination['country'].'-'.$destination['region']]);
                    $shippingRuleDestination->setRegion($region);
                }

                if (array_key_exists('postalCode', $destination)) {
                    $shippingRuleDestination->setPostalCode($destination['postalCode']);
                }
                $manager->persist($shippingRuleDestination);
                $entity->addDestination($shippingRuleDestination);
            }
            
            $shippingMethods = $this->container->get('oro_shipping.shipping_method.registry')->getShippingMethods();

            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod instanceof UPSShippingMethod) {
                    $methodConfig = new ShippingRuleMethodConfig();
                    $methodConfig
                        ->setRule($entity)
                        ->setMethod($shippingMethod->getIdentifier());

                    foreach ($shippingMethod->getTypes() as $shippingMethodType) {
                        $typeConfig = new ShippingRuleMethodTypeConfig();
                        $typeConfig->setType($shippingMethodType->getIdentifier());
                        $typeConfig->setEnabled(false);
                        $methodConfig->addTypeConfig($typeConfig);
                    }

                    $manager->persist($methodConfig);
                    $entity->addMethodConfig($methodConfig);
                }
            }
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingRuleData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_rules.yml'));
    }
}
