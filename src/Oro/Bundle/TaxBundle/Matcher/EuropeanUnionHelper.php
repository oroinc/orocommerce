<?php

namespace Oro\Bundle\TaxBundle\Matcher;

class EuropeanUnionHelper
{
    /**
     * @var array
     */
    public static $europeanUnionCountryCodes = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK',
        'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL',
        'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'UK'
    ];

    /**
     * @param string $countryCode
     * @return bool
     */
    public static function isEuropeanUnionCountry($countryCode)
    {
        return in_array($countryCode, self::$europeanUnionCountryCodes, true);
    }
}
