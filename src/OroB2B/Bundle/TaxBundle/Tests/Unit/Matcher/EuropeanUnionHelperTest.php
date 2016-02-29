<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use OroB2B\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;

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
