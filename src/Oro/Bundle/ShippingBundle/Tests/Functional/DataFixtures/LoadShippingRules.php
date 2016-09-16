<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Symfony\Component\Yaml\Yaml;

class LoadShippingRules extends AbstractFixture
{
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

            foreach ($data['methodConfigs'] as $methodConfigData) {
                $methodConfig = new ShippingRuleMethodConfig();

                $methodConfig
                    ->setRule($entity)
                    ->setMethod(FlatRateShippingMethod::IDENTIFIER);

                foreach ($methodConfigData['typeConfigs'] as $typeConfigData) {
                    $typeConfig = new ShippingRuleMethodTypeConfig();
                    $typeConfig->setType(FlatRateShippingMethodType::IDENTIFIER)
                        ->setOptions([
                            FlatRateShippingMethodType::PRICE_OPTION => $typeConfigData['options']['price'],
                            FlatRateShippingMethodType::HANDLING_FEE_OPTION => null,
                            FlatRateShippingMethodType::TYPE_OPTION => $typeConfigData['options']['type'],
                        ]);
                    $typeConfig->setEnabled(true);
                    $methodConfig->addTypeConfig($typeConfig);
                }

                $manager->persist($methodConfig);
                $entity->addMethodConfig($methodConfig);
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
