<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Symfony\Component\Yaml\Yaml;

class LoadPaymentMethodsConfigsRuleDestinationData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getPaymentMethodsConfigsRulesData() as $reference => $data) {
            $entity = new PaymentMethodsConfigsRuleDestination();

            /** @var Country $country */
            $country = $manager->getRepository(Country::class)
                ->findOneBy(['iso2Code' => $data['iso2_country_code']]);

            /** @var Region $region */
            $region = $manager->getRepository(Region::class)
                ->findOneBy(['combinedCode' => $data['iso2_country_code'].'-'.$data['region_code']]);

            /** @var PaymentMethodsConfigsRule $configsRule */
            $configsRule = $this->getReference($data['payment_methods_configs_rule_reference']);

            $entity
                ->setRegion($region)
                ->setCountry($country)
                ->setMethodsConfigsRule($configsRule);

            if (array_key_exists('postal_codes', $data)) {
                foreach ($data['postal_codes'] as $postalCode) {
                    $newPostalCode = new PaymentMethodsConfigsRuleDestinationPostalCode();
                    $newPostalCode->setName($postalCode['name']);
                    $newPostalCode->setDestination($entity);

                    $manager->persist($newPostalCode);
                    $entity->addPostalCode($newPostalCode);
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
    protected function getPaymentMethodsConfigsRulesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/basic_payment_methods_configs_rules_destinations.yml'));
    }
}
