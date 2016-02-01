<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Matcher\RegionMatcher;

class RegionMatcherTest extends AbstractMatcherTest
{
    /**
     * @var CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new RegionMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);

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
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @param TaxRule[] $countryMatcherTaxRules
     * @param TaxRule[] $regionTaxRules
     * @param TaxRule[] $expected
     */
    public function testMatch($country, $region, $regionText, $countryMatcherTaxRules, $regionTaxRules, $expected)
    {
        $address = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->countryMatcher
            ->expects($this->once())
            ->method('match')
            ->with($address)
            ->willReturn($countryMatcherTaxRules);

        $this->taxRuleRepository
            ->expects(empty($regionTaxRules) ? $this->never() : $this->once())
            ->method('findByCountryAndRegion')
            ->with($country, $region, $regionText)
            ->willReturn($regionTaxRules);

        $this->assertEquals($expected, $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $regionText = 'Alaska';

        $countryMatcherTaxRules = [
            $this->getTaxRule(1),
        ];

        $regionTaxRules = [
            $this->getTaxRule(1),
            $this->getTaxRule(2),
        ];

        return [
            'with country and region' => [
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => $regionTaxRules,
                'expected' => $regionTaxRules,
            ],
            'with country and regionText' => [
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => $regionTaxRules,
                'expected' => $regionTaxRules,
            ],
            'without country' => [
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without region and region text' => [
                'country' => $country,
                'region' => null,
                'regionText' => '',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
        ];
    }
}
