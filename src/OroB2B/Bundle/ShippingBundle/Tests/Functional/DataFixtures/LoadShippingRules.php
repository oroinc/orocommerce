<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;
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

            foreach ($data['configurations'] as $configuration) {
                $flatConfig = new FlatRateRuleConfiguration();

                $flatConfig
                    ->setRule($entity)
                    ->setType(FlatRateShippingMethod::NAME)
                    ->setMethod(FlatRateShippingMethod::NAME)
                    ->setProcessingType($configuration['processingType'])
                    ->setValue($configuration['value'])
                    ->setCurrency($configuration['currency']);

                $manager->persist($flatConfig);
                $entity->addConfiguration($flatConfig);
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
