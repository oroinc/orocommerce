<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;

class LoadShippingServicesData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $data) {
            /** @var ShippingServiceRule $rule */
            $rule = $this->getReference($data['rule']);

            $service = new FedexShippingService();
            $service
                ->setDescription($data['description'])
                ->setCode($data['code'])
                ->setRule($rule);

            $manager->persist($service);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'code' => 'PRIORITY_OVERNIGHT',
                'description' => 'FedEx Priority Overnight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],
            [
                'code' => 'STANDARD_OVERNIGHT',
                'description' => 'FedEx Standard Overnight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],
            [
                'code' => 'FEDEX_2_DAY',
                'description' => 'FedEx 2 Day',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],
            [
                'code' => 'FEDEX_2_DAY_AM',
                'description' => 'FedEx 2 Day AM',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],
            [
                'code' => 'FEDEX_EXPRESS_SAVER',
                'description' => 'FedEx Express Saver',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],
            [
                'code' => 'FIRST_OVERNIGHT',
                'description' => 'FedEx First Overnight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE1,
            ],

            [
                'code' => 'FEDEX_1_DAY_FREIGHT',
                'description' => 'FedEx 1 Day Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],
            [
                'code' => 'FEDEX_2_DAY_FREIGHT',
                'description' => 'FedEx 2 Day Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],
            [
                'code' => 'FEDEX_3_DAY_FREIGHT',
                'description' => 'FedEx 3 Day Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],
            [
                'code' => 'FEDEX_FIRST_FREIGHT',
                'description' => 'FedEx First Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],

            [
                'code' => 'FEDEX_GROUND',
                'description' => 'FedEx Ground',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE3,
            ],
            [
                'code' => 'GROUND_HOME_DELIVERY',
                'description' => 'FedEx Ground Home Delivery',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE4,
            ],

            [
                'code' => 'INTERNATIONAL_FIRST',
                'description' => 'FedEx International First',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE5,
            ],
            [
                'code' => 'INTERNATIONAL_PRIORITY',
                'description' => 'FedEx International Priority',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE5,
            ],
            [
                'code' => 'INTERNATIONAL_ECONOMY',
                'description' => 'FedEx International Economy',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE5,
            ],
            [
                'code' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                'description' => 'FedEx Europe First International Priority',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE5,
            ],

            [
                'code' => 'INTERNATIONAL_PRIORITY_FREIGHT',
                'description' => 'FedEx International Priority Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],
            [
                'code' => 'INTERNATIONAL_ECONOMY_FREIGHT',
                'description' => 'FedEx International Economy Freight',
                'rule' => LoadShippingServiceRulesData::REFERENCE_RULE2,
            ],
        ];
    }
}
