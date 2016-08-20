<?php

namespace Oro\Bundle\TaxBundle\Matcher;

class UnitedStatesHelper
{
    const COUNTRY_CODE_USA = 'US';

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
