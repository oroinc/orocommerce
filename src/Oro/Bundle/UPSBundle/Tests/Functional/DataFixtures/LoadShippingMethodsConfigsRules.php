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
    private ContainerInterface $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadChannelData::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShippingMethodsConfigsRuleData() as $reference => $data) {
            $rule = new Rule();
            $rule->setName($reference);
            $rule->setEnabled($data['rule']['enabled']);
            $rule->setSortOrder($data['rule']['sortOrder']);
            $rule->setExpression($data['rule']['expression']);

            $entity = new ShippingMethodsConfigsRule();
            $entity->setRule($rule);
            $entity->setCurrency($data['currency']);
            $manager->persist($entity);

            $this->setDestinations($entity, $manager, $data);

            /** @var array $shippingMethods */
            $shippingMethods = $this->container->get('oro_shipping.shipping_method_provider')->getShippingMethods();
            $this->setShippingMethods($entity, $manager, $shippingMethods);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    private function getShippingMethodsConfigsRuleData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_methods_configs_rules.yml'));
    }

    private function setDestinations(ShippingMethodsConfigsRule $entity, ObjectManager $manager, array $data): void
    {
        if (!\array_key_exists('destinations', $data)) {
            $data['destinations'] = [];
        }
        foreach ($data['destinations'] as $destination) {
            /** @var Country $country */
            $country = $manager->getRepository(Country::class)
                ->findOneBy(['iso2Code' => $destination['country']]);

            $shippingMethodsConfigsRuleDestination = new ShippingMethodsConfigsRuleDestination();
            $shippingMethodsConfigsRuleDestination->setMethodConfigsRule($entity);
            $shippingMethodsConfigsRuleDestination->setCountry($country);

            if (\array_key_exists('region', $destination)) {
                /** @var Region $region */
                $region = $manager->getRepository(Region::class)
                    ->findOneBy(['combinedCode' => $destination['country'].'-'.$destination['region']]);
                $shippingMethodsConfigsRuleDestination->setRegion($region);
            }

            if (\array_key_exists('postalCodes', $destination)) {
                /** @var array $postalCode */
                foreach ($destination['postalCodes'] as $postalCode) {
                    $destinationPostalCode = new ShippingMethodsConfigsRuleDestinationPostalCode();
                    $destinationPostalCode->setName($postalCode['name']);
                    $destinationPostalCode->setDestination($shippingMethodsConfigsRuleDestination);

                    $shippingMethodsConfigsRuleDestination->addPostalCode($destinationPostalCode);
                }
            }

            $manager->persist($shippingMethodsConfigsRuleDestination);
            $entity->addDestination($shippingMethodsConfigsRuleDestination);
        }
    }

    private function setShippingMethods(
        ShippingMethodsConfigsRule $entity,
        ObjectManager $manager,
        array $shippingMethods
    ): void {
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof UPSShippingMethod) {
                $methodConfig = new ShippingMethodConfig();
                $methodConfig->setMethodConfigsRule($entity);
                $methodConfig->setMethod($shippingMethod->getIdentifier());

                /** @var UPSShippingMethodType $shippingMethodType */
                foreach ($shippingMethod->getTypes() as $shippingMethodType) {
                    $typeConfig = new ShippingMethodTypeConfig();
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
