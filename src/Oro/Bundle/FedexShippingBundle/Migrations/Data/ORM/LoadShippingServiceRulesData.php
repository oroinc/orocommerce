<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;

class LoadShippingServiceRulesData extends AbstractFixture implements OrderedFixtureInterface
{
    const REFERENCE_RULE1 = 'fedex_shipping_service_rule1';
    const REFERENCE_RULE2 = 'fedex_shipping_service_rule2';
    const REFERENCE_RULE3 = 'fedex_shipping_service_rule3';
    const REFERENCE_RULE4 = 'fedex_shipping_service_rule4';
    const REFERENCE_RULE5 = 'fedex_shipping_service_rule5';

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $data) {
            $rule = new ShippingServiceRule();
            $rule
                ->setLimitationExpressionLbs($data['expressionLbs'])
                ->setLimitationExpressionKg($data['expressionKg'])
                ->setServiceType($data['serviceType'])
                ->setResidentialAddress($data['residentialAddress']);

            $this->setReference($data['reference'], $rule);

            $manager->persist($rule);
        }

        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'reference' => self::REFERENCE_RULE1,
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68.04 and length <= 302.26 and (length + 2*width + 2*height <= 419.1)',
                'serviceType' => null,
                'residentialAddress' => false,
            ],
            [
                'reference' => self::REFERENCE_RULE2,
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302.26 and width <= 302.26 and height <= 178',
                'serviceType' => null,
                'residentialAddress' => false,
            ],
            [
                'reference' => self::REFERENCE_RULE3,
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68.04 and length <= 274.32 and (length + 2*width + 2*height <= 419.1)',
                'serviceType' => null,
                'residentialAddress' => false,
            ],
            [
                'reference' => self::REFERENCE_RULE4,
                'expressionLbs' => 'weight <= 70 and length <= 108 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 32 and length <= 274.32 and (length + 2*width + 2*height <= 419.1)',
                'serviceType' => 'GROUND_HOME_DELIVERY',
                'residentialAddress' => true,
            ],
            [
                'reference' => self::REFERENCE_RULE5,
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 130)',
                'expressionKg' => 'weight <= 68.04 and length <= 274.32 and (length + 2*width + 2*height <= 330.2)',
                'serviceType' => null,
                'residentialAddress' => false,
            ],
        ];
    }
}
