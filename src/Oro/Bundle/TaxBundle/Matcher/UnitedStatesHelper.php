<?php

namespace Oro\Bundle\TaxBundle\Matcher;

/**
 * Helper class for the United States tax-related logic.
 *
 * This helper provides functionality specific to US tax calculations, particularly for identifying states
 * that do not impose taxes on digital products. It is used in tax calculation logic to apply US-specific tax rules
 * and exemptions based on the customer's location within the United States.
 */
class UnitedStatesHelper
{
    public const COUNTRY_CODE_USA = 'US';

    /**
     * @var array
     */
    public static $statesWithoutDigitalTax = [
        'CA', 'DC', 'FL', 'GA', 'IA', 'IL', 'KS',
        'MD', 'MA', 'MI', 'MN', 'NY', 'NV', 'ND',
        'OH', 'OK', 'PA', 'RI', 'SC', 'VA', 'WV'
    ];

    /**
     * @param string $countryCode
     * @param string $regionCode
     * @return bool
     */
    public static function isStateWithoutDigitalTax($countryCode, $regionCode)
    {
        return self::COUNTRY_CODE_USA === $countryCode &&
        in_array($regionCode, self::$statesWithoutDigitalTax, true);
    }
}
