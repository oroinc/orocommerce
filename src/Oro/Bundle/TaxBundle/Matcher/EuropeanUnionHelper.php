<?php

namespace Oro\Bundle\TaxBundle\Matcher;

/**
 * Helper class for identifying European Union member countries.
 *
 * This helper provides functionality to determine whether a given country code belongs to a European Union
 * member state. It is used in tax calculation logic to apply EU-specific tax rules, such as VAT regulations
 * for cross-border transactions within the European Union.
 */
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
