<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadPriceListSchedulesSimplified extends LoadPriceListSchedules implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $data = [
        [
            'priceList' => LoadPriceLists::PRICE_LIST_1,
            'schedules' => [
                ['name' => 'schedule.1', 'activateAt' => null, 'deactivateAt' => '-2 day'],
            ]
        ],
        [
            'priceList' => LoadPriceLists::PRICE_LIST_2,
            'schedules' => [
                ['name' => 'schedule.2', 'activateAt' => '-1 day', 'deactivateAt' => null],
            ]
        ],
    ];
}
