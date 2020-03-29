<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadCombinedPriceListsActivationRulesForRepository extends LoadCombinedPriceListsActivationRules
{
    /**
     * @var array
     */
    protected $data = [
        [
            'fullCombinedPriceList' => '2f_1t_3t',
            'combinedPriceList' => '2f',
            'activateAtOffset' => '+12 hours',
            'expiredAtOffset' => '+24 hours',
            'active' => true
        ],
        [
            'fullCombinedPriceList' => '2f_1t_3t',
            'combinedPriceList' => '2f',
            'activateAtOffset' => '+2 days',
            'expiredAtOffset' => '+3 days',
            'active' => false,
        ],
        [
            'fullCombinedPriceList' => '1f',
            'combinedPriceList' => '2f',
            'activateAtOffset' => null,
            'expiredAtOffset' => '+5 days',
            'active' => false,
        ],
        [
            'fullCombinedPriceList' => '1f',
            'combinedPriceList' => '1f',
            'activateAtOffset' => '+6 days',
            'expiredAtOffset' => null,
            'active' => false,
        ],
    ];
}
