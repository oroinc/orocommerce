<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;

class EuropeanUnionHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEuropeanUnionCountry()
    {
        foreach (EuropeanUnionHelper::$europeanUnionCountryCodes as $europeanCode) {
            $this->assertTrue(EuropeanUnionHelper::isEuropeanUnionCountry($europeanCode));
        }

        $this->assertFalse(EuropeanUnionHelper::isEuropeanUnionCountry('NON_EU_COUNTRY'));
    }
}
