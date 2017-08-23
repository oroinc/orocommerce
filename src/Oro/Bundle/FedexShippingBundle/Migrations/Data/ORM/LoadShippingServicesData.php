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
            'FedEx Europe First International Priority' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY',
            'FedEx 1 Day Freight' => 'FEDEX_1_DAY_FREIGHT',
            'FedEx 2 Day' => 'FEDEX_2_DAY',
            'FedEx 2 Day AM' => 'FEDEX_2_DAY_AM',
            'FedEx 2 Day Freight' => 'FEDEX_2_DAY_FREIGHT',
            'FedEx 3 Day Freight' => 'FEDEX_3_DAY_FREIGHT',
            'FedEx Express Saver' => 'FEDEX_EXPRESS_SAVER',
            'FedEx First Freight' => 'FEDEX_FIRST_FREIGHT',
            'FedEx Ground' => 'FEDEX_GROUND',
            'FedEx Ground Home Delivery' => 'GROUND_HOME_DELIVERY',
            'FedEx First Overnight' => 'FIRST_OVERNIGHT',
            'FedEx International Distribution Freight' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT',
            'FedEx International Economy' => 'INTERNATIONAL_ECONOMY',
            'FedEx International Economy Distribution' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION',
            'FedEx International Economy Freight' => 'INTERNATIONAL_ECONOMY_FREIGHT',
            'FedEx International First' => 'INTERNATIONAL_FIRST',
            'FedEx International Priority' => 'INTERNATIONAL_PRIORITY',
            'FedEx International Priority Distribution' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
            'FedEx International Priority Freight' => 'INTERNATIONAL_PRIORITY_FREIGHT',
            'FedEx Priority Overnight' => 'PRIORITY_OVERNIGHT',
            'FedEx Standard Overnight' => 'STANDARD_OVERNIGHT',
        ];
    }
}
