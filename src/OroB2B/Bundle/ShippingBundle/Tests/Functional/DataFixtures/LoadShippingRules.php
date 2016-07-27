<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class LoadShippingRules extends AbstractFixture
{
    const SHIPPING_RULE_1 = 'shipping_rule.1';

    /**
     * @var array
     */
    protected $data = [
        self::SHIPPING_RULE_1 => [
            'name'                 => 'Rule 1',
            'enabled'              => true,
            'priority'             => 0,
            'conditions'           => 'condition 1',
            'currency'             => 'EUR',
            'configurations'       => [
                [
                    'class'    => 'flatrateruleconfiguration',
                    'type'     => 'UPS',
                    'method'   => 'Ground',
                    'value'    => 10,
                    'currency' => 'EUR',
                ],
                [
                    'class'    => 'flatrateruleconfiguration',
                    'type'     => 'UPS',
                    'method'   => 'Next Day Air',
                    'value'    => 20,
                    'currency' => 'EUR',
                ]

            ],
            'shippingDestinations' => [
                [
                    'postalCode' => '12345',
                    'country'    => 'US',
                    'region'     => 'NY'
                ]
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $reference => $data) {
            $entity = new ShippingRule();
            $entity
                ->setName($data['name'])
                ->setEnabled($data['enabled'])
                ->setPriority($data['priority'])
                ->setConditions($data['conditions'])
                ->setCurrency($data['currency']);

            foreach ($data['shippingDestinations'] as $destination) {
                /** @var Country $country */
                $country = $manager
                    ->getRepository('OroAddressBundle:Country')
                    ->findOneBy(['iso2Code' => $destination['country']]);
                /** @var Region $region */
                $region = $manager
                    ->getRepository('OroAddressBundle:Region')
                    ->findOneBy(['combinedCode' => $destination['country'] . '-' . $destination['region']]);

                $shippingDestination = new ShippingDestination();
                $shippingDestination
                    ->setShippingRule($entity)
                    ->setPostalCode($destination['postalCode'])
                    ->setCountry($country)
                    ->setRegion($region);

                $manager->persist($shippingDestination);
                $entity->addShippingDestination($shippingDestination);
            }

            foreach ($data['configurations'] as $configuration) {
                if ($configuration['class'] == 'flatrateruleconfiguration') {
                    $flatConfig = new FlatRateRuleConfiguration();

                    $flatConfig
                        ->setRule($entity)
                        ->setType($configuration['type'])
                        ->setMethod($configuration['method'])
                        ->setValue($configuration['value'])
                        ->setCurrency($configuration['currency'])
                        ->createPrice();

                    $manager->persist($flatConfig);
                    $entity->addConfiguration($flatConfig);
                }

            }

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }
}
