<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Matcher\ZipCodeMatcher;

class ZipCodeMatcherTest extends AbstractMatcherTest
{
    use EntityTrait;

    const REGION_AS_OBJECT = 'region_as_object';
    const REGION_AS_TEXT = 'region_as_text';

    const REGION_TEXT = 'Alaska';
    const POSTAL_CODE = '02097';

    /**
     * @var CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new ZipCodeMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);

        $this->countryMatcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher->setCountryMatcher($this->countryMatcher);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->countryMatcher);
    }

    /**
     * @dataProvider matchProvider
     * @param string|false $regionPresent
     */
    public function testMatch($regionPresent)
    {
        $country = new Country('US');
        $region = new Region('US-NY');
        $region->setCountry($country);

        $address = new Address();
        $address->setPostalCode(self::POSTAL_CODE);

        if ($regionPresent === self::REGION_AS_OBJECT) {
            $address->setRegion($region);
        } elseif ($regionPresent === self::REGION_AS_TEXT) {
            $address->setCountry($country);
            $address->setRegionText(self::REGION_TEXT);
        }

        $countryMatcherTaxRules = [
            $this->getTaxRule(1)
        ];

        $this->countryMatcher
            ->expects($this->once())
            ->method('match')
            ->with($address)
            ->willReturn($countryMatcherTaxRules);

        $zipCodeMatcherTaxRules = [
            $this->getTaxRule(1),
            $this->getTaxRule(2)
        ];

        $this->taxRuleRepository
            ->expects($this->once())
            ->method('findByZipCode')
            ->with(
                self::POSTAL_CODE,
                $address->getRegion(),
                $regionPresent === self::REGION_AS_TEXT ? $address->getRegionText() : null,
                $regionPresent === self::REGION_AS_TEXT ? $address->getCountry() : null
            )
            ->willReturn($zipCodeMatcherTaxRules);

        $this->assertEquals($zipCodeMatcherTaxRules, $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        return [
            'with region as object' => [
                'regionPresent' => self::REGION_AS_OBJECT,
            ],
            'with region as text' => [
                'regionPresent' => self::REGION_AS_TEXT,
            ],
        ];
    }

    /**
     * @param int $id
     * @return TaxRule
     */
    protected function getTaxRule($id)
    {
        return $this->getEntity('OroB2B\Bundle\TaxBundle\Entity\TaxRule', ['id' => $id]);
    }
}
