<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;

class LoadShippingServicesData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $data) {
            $service = new ShippingService();

            $service
                ->setDescription($data['description'])
                ->setCode($data['code'])
                ->setLimitationExpressionLbs($data['expressionLbs'])
                ->setLimitationExpressionKg($data['expressionKg']);

            $manager->persist($service);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    private function getData(): array
    {
        return [
            [
                'code' => 'PRIORITY_OVERNIGHT',
                'description' => 'FedEx Priority Overnight',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'STANDARD_OVERNIGHT',
                'description' => 'FedEx Standard Overnight',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'FEDEX_2_DAY',
                'description' => 'FedEx 2 Day',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'FEDEX_2_DAY_AM',
                'description' => 'FedEx 2 Day AM',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'FEDEX_EXPRESS_SAVER',
                'description' => 'FedEx Express Saver',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'FIRST_OVERNIGHT',
                'description' => 'FedEx First Overnight',
                'expressionLbs' => 'weight <= 150 and length <= 119 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 302 and (length + 2*width + 2*height <= 419)',
            ],

            [
                'code' => 'FEDEX_1_DAY_FREIGHT',
                'description' => 'FedEx 1 Day Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],
            [
                'code' => 'FEDEX_2_DAY_FREIGHT',
                'description' => 'FedEx 2 Day Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],
            [
                'code' => 'FEDEX_3_DAY_FREIGHT',
                'description' => 'FedEx 3 Day Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],
            [
                'code' => 'FEDEX_FIRST_FREIGHT',
                'description' => 'FedEx First Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],

            [
                'code' => 'FEDEX_GROUND',
                'description' => 'FedEx Ground',
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 68 and length <= 274 and (length + 2*width + 2*height <= 419)',
            ],
            [
                'code' => 'GROUND_HOME_DELIVERY',
                'description' => 'FedEx Ground Home Delivery',
                'expressionLbs' => 'weight <= 70 and length <= 108 and (length + 2*width + 2*height <= 165)',
                'expressionKg' => 'weight <= 32 and length <= 274 and (length + 2*width + 2*height <= 419)',
            ],

            [
                'code' => 'INTERNATIONAL_FIRST',
                'description' => 'FedEx International First',
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 130)',
                'expressionKg' => 'weight <= 68 and length <= 274 and (length + 2*width + 2*height <= 330)',
            ],
            [
                'code' => 'INTERNATIONAL_PRIORITY',
                'description' => 'FedEx International Priority',
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 130)',
                'expressionKg' => 'weight <= 68 and length <= 274 and (length + 2*width + 2*height <= 330)',
            ],
            [
                'code' => 'INTERNATIONAL_ECONOMY',
                'description' => 'FedEx International Economy',
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 130)',
                'expressionKg' => 'weight <= 68 and length <= 274 and (length + 2*width + 2*height <= 330)',
            ],
            [
                'code' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
                'description' => 'FedEx Europe First International Priority',
                'expressionLbs' => 'weight <= 150 and length <= 108 and (length + 2*width + 2*height <= 130)',
                'expressionKg' => 'weight <= 68 and length <= 274 and (length + 2*width + 2*height <= 330)',
            ],

            [
                'code' => 'INTERNATIONAL_PRIORITY_FREIGHT',
                'description' => 'FedEx International Priority Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],
            [
                'code' => 'INTERNATIONAL_ECONOMY_FREIGHT',
                'description' => 'FedEx International Economy Freight',
                'expressionLbs' => 'weight <= 2200 and length <= 119 and height <= 70 and width <= 119',
                'expressionKg' => 'weight <= 998 and length <= 302 and height <= 178 and width <= 302',
            ],
        ];
    }
}
