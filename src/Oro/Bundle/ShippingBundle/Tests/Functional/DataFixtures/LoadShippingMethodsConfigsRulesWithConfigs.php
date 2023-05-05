<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures\LoadFlatRateIntegration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Symfony\Component\Yaml\Yaml;

class LoadShippingMethodsConfigsRulesWithConfigs extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadFlatRateIntegration::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShippingRuleData() as $reference => $data) {
            $this->loadShippingRule($reference, $data, $manager);
        }

        $manager->flush();
    }

    protected function getShippingRuleData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_methods_configs_rules_with_configs.yml'));
    }

    private function loadShippingRule(string $reference, array $data, ObjectManager $manager): void
    {
        $rule = $this->buildRule($reference, $data, $manager);
        $configRule = $this->buildMethodsConfigsRule($reference, $data, $rule, $manager);

        $this->setReference($reference, $configRule);

        $manager->persist($configRule);
    }

    protected function buildMethodsConfigsRule(
        string $reference,
        array $data,
        RuleInterface $rule,
        ObjectManager $manager
    ): ShippingMethodsConfigsRule {
        $configRule = new ShippingMethodsConfigsRule();
        $configRule->setRule($rule);
        $configRule->setCurrency($data['currency']);
        $configRule->setOrganization($manager->getRepository(Organization::class)->getFirst());

        $this->setDestinations($configRule, $manager, $data);
        $this->setMethodConfigs($configRule, $manager, $data);

        return $configRule;
    }

    protected function buildRule(string $reference, array $data, ObjectManager $manager): Rule
    {
        $rule = new Rule();
        $rule->setName($reference);
        $rule->setEnabled($data['rule']['enabled']);
        $rule->setSortOrder($data['rule']['sortOrder']);
        $rule->setExpression($data['rule']['expression']);

        return $rule;
    }

    private function setDestinations(ShippingMethodsConfigsRule $configRule, ObjectManager $manager, array $data): void
    {
        if (!array_key_exists('destinations', $data)) {
            return;
        }

        foreach ($data['destinations'] as $destination) {
            /** @var Country $country */
            $country = $manager->getRepository(Country::class)
                ->findOneBy(['iso2Code' => $destination['country']]);

            $shippingRuleDestination = new ShippingMethodsConfigsRuleDestination();
            $shippingRuleDestination
                ->setMethodConfigsRule($configRule)
                ->setCountry($country);

            if (array_key_exists('region', $destination)) {
                /** @var Region $region */
                $region = $manager->getRepository(Region::class)
                    ->findOneBy(['combinedCode' => $destination['country'].'-'.$destination['region']]);
                $shippingRuleDestination->setRegion($region);
            }

            if (array_key_exists('postalCodes', $destination)) {
                foreach ($destination['postalCodes'] as $postalCode) {
                    $destinationPostalCode = new ShippingMethodsConfigsRuleDestinationPostalCode();
                    $destinationPostalCode->setName($postalCode['name']);
                    $destinationPostalCode->setDestination($shippingRuleDestination);

                    $shippingRuleDestination->addPostalCode($destinationPostalCode);
                }
            }

            $manager->persist($shippingRuleDestination);
            $configRule->addDestination($shippingRuleDestination);
        }
    }

    private function setMethodConfigs(ShippingMethodsConfigsRule $configRule, ObjectManager $manager, array $data): void
    {
        if (!array_key_exists('methodConfigs', $data)) {
            return;
        }

        foreach ($data['methodConfigs'] as $methodConfigData) {
            $methodConfig = $this->buildMethodConfig($configRule);

            foreach ($methodConfigData['typeConfigs'] as $typeConfigData) {
                $typeConfig = new ShippingMethodTypeConfig();
                $typeConfig->setType('primary');
                $typeConfig->setOptions([
                    'price' => $typeConfigData['options']['price'],
                    'handling_fee' => null,
                    'type' => $typeConfigData['options']['type'],
                ]);
                $typeConfig->setEnabled($typeConfigData['enabled']);
                $methodConfig->addTypeConfig($typeConfig);
            }

            $configRule->addMethodConfig($methodConfig);

            $manager->persist($methodConfig);
        }
    }

    private function buildMethodConfig(ShippingMethodsConfigsRule $configRule): ShippingMethodConfig
    {
        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethodConfigsRule($configRule);
        $methodConfig->setMethod($this->getFlatRateIdentifier());

        return $methodConfig;
    }

    private function getFlatRateIdentifier(): string
    {
        return sprintf('flat_rate_%s', $this->getReference(LoadFlatRateIntegration::REFERENCE_FLAT_RATE)->getId());
    }
}
