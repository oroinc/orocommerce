<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class LoadPriceListSchedules extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'priceList' => 'price_list_1',
            'schedules' => [
                ['name' => 'schedule.1', 'activateAt' => '-1 day', 'deactivateAt' => '+1 day'],
                ['name' => 'schedule.2', 'activateAt' => '+1 day', 'deactivateAt' => null],
                ['name' => 'schedule.3', 'activateAt' => '-2 day', 'deactivateAt' => '-1 day'],
            ]
        ],
        [
            'priceList' => 'price_list_2',
            'schedules' => [
                ['name' => 'schedule.4', 'activateAt' => '-2 day', 'deactivateAt' => '-1 day'],
            ]
        ],
        [
            'priceList' => 'price_list_3',
            'schedules' => [
                ['name' => 'schedule.5', 'activateAt' => null, 'deactivateAt' => '+1 day'],
            ]
        ],
        [
            'priceList' => 'price_list_4',
            'schedules' => [
                ['name' => 'schedule.6', 'activateAt' => '+1 day', 'deactivateAt' => null],
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        foreach ($this->data as $schedulesData) {
            /** @var PriceList $priceList */
            $priceList = $this->getReference($schedulesData['priceList']);

            foreach ($schedulesData['schedules'] as $scheduleData) {
                $schedule = new PriceListSchedule();
                $priceList->addSchedule($schedule);
                if ($scheduleData['activateAt']) {
                    $scheduleDate = clone $now;
                    $scheduleDate->modify($scheduleData['activateAt']);
                    $schedule->setActiveAt($scheduleDate);
                }
                if ($scheduleData['deactivateAt']) {
                    $scheduleDate = clone $now;
                    $scheduleDate->modify($scheduleData['deactivateAt']);
                    $schedule->setDeactivateAt($scheduleDate);
                }
                $this->setReference($scheduleData['name'], $schedule);
            }
            $manager->persist($priceList);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'];
    }
}
