<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadShippingMethodsConfigsRules extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
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
        $entityBase = new ShippingMethodsConfigsRule();
        $ruleBase = new Rule();

        foreach ($this->getShippingMethodsConfigsRuleData() as $reference => $data) {
            $rule = clone $ruleBase;
            $rule->setName($reference)
                ->setEnabled($data['rule']['enabled'])
                ->setSortOrder($data['rule']['sortOrder'])
                ->setExpression($data['rule']['expression']);

            $entity = clone $entityBase;
            $entity->setRule($rule)
                ->setCurrency($data['currency']);

            $this->setDestinations($entity, $manager, $data);

            /** @var array $shippingMethods */
            $shippingMethods = $this->container->get('oro_shipping.shipping_method_provider')->getShippingMethods();
            $this->setShippingMethods($entity, $manager, $shippingMethods);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingMethodsConfigsRuleData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_methods_configs_rules.yml'));
    }

    /**
     * @param ShippingMethodsConfigsRule $entity
     * @param ObjectManager $manager
     * @param array $data
     */
    protected function setDestinations(ShippingMethodsConfigsRule $entity, ObjectManager $manager, $data)
    {
        $shippingMethodsConfigsRuleDestinationBase = new ShippingMethodsConfigsRuleDestination();
        $destinationPostalCodeBase = new ShippingMethodsConfigsRuleDestinationPostalCode();

        if (!array_key_exists('destinations', $data)) {
            $data['destinations'] = [];
        }
        foreach ($data['destinations'] as $destination) {
            /** @var Country $country */
            $country = $manager
                ->getRepository('OroAddressBundle:Country')
                ->findOneBy(['iso2Code' => $destination['country']]);

            $shippingMethodsConfigsRuleDestination = clone $shippingMethodsConfigsRuleDestinationBase;
            $shippingMethodsConfigsRuleDestination
                ->setMethodConfigsRule($entity)
                ->setCountry($country);

            if (array_key_exists('region', $destination)) {
                /** @var Region $region */
                $region = $manager
                    ->getRepository('OroAddressBundle:Region')
                    ->findOneBy(['combinedCode' => $destination['country'].'-'.$destination['region']]);
                $shippingMethodsConfigsRuleDestination->setRegion($region);
            }

            if (array_key_exists('postalCodes', $destination)) {
                /** @var array $postalCode */
                foreach ($destination['postalCodes'] as $postalCode) {
                    $destinationPostalCode = clone $destinationPostalCodeBase;
                    $destinationPostalCode->setName($postalCode['name'])
                        ->setDestination($shippingMethodsConfigsRuleDestination);

                    $shippingMethodsConfigsRuleDestination->addPostalCode($destinationPostalCode);
                }
            }

            $manager->persist($shippingMethodsConfigsRuleDestination);
            $entity->addDestination($shippingMethodsConfigsRuleDestination);
        }
    }

    /**
     * @param ShippingMethodsConfigsRule $entity
     * @param ObjectManager $manager
     * @param array $shippingMethods
     */
    protected function setShippingMethods(ShippingMethodsConfigsRule $entity, ObjectManager $manager, $shippingMethods)
    {
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof UPSShippingMethod) {
                $methodConfig = new ShippingMethodConfig();
                $methodConfig
                    ->setMethodConfigsRule($entity)
                    ->setMethod($shippingMethod->getIdentifier());

                $typeConfigBase = new ShippingMethodTypeConfig();
                /** @var UPSShippingMethodType $shippingMethodType */
                foreach ($shippingMethod->getTypes() as $shippingMethodType) {
                    $typeConfig = clone $typeConfigBase;
                    $typeConfig->setType($shippingMethodType->getIdentifier());
                    $typeConfig->setEnabled(true);
                    $methodConfig->addTypeConfig($typeConfig);
                }

                $manager->persist($methodConfig);
                $entity->addMethodConfig($methodConfig);
            }
        }
    }
}
