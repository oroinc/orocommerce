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
        foreach ($this->getData() as $description => $code) {
            $service = new ShippingService();

            $service
                ->setDescription($description)
                ->setCode($code);

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
            'Europe First International Priority' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
            '1 Day Freight' => 'FEDEX_1_DAY_FREIGHT',
            '2 Day' => 'FEDEX_2_DAY',
            '2 Day AM' => 'FEDEX_2_DAY_AM',
            '2 Day Freight' => 'FEDEX_2_DAY_FREIGHT',
            '3 Day Freight' => 'FEDEX_3_DAY_FREIGHT',
            'Express Saver' => 'FEDEX_EXPRESS_SAVER',
            'First Freight' => 'FEDEX_FIRST_FREIGHT',
            'Ground' => 'FEDEX_GROUND',
            'Ground Home Delivery' => 'GROUND_HOME_DELIVERY',
            'First Overnight' => 'FIRST_OVERNIGHT',
            'International Distribution Freight' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT',
            'International Economy' => 'INTERNATIONAL_ECONOMY',
            'International Economy Distribution' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION',
            'International Economy Freight' => 'INTERNATIONAL_ECONOMY_FREIGHT',
            'International First' => 'INTERNATIONAL_FIRST',
            'International Priority' => 'INTERNATIONAL_PRIORITY',
            'International Priority Distribution' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
            'International Priority Freight' => 'INTERNATIONAL_PRIORITY_FREIGHT',
            'Priority Overnight' => 'PRIORITY_OVERNIGHT',
            'Standard Overnight' => 'STANDARD_OVERNIGHT',
        ];
    }
}
