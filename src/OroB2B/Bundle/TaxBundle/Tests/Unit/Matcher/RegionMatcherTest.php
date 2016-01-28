<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

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
     */
    public function testMatch($country, $region, $regionText)
    {
        $address = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $countryMatcherTaxRules = [
            $this->getTaxRule(1)
        ];

        $this->countryMatcher
            ->expects($this->once())
            ->method('match')
            ->with($address)
            ->willReturn($countryMatcherTaxRules);

        $regionTaxRules = [
            $this->getTaxRule(1),
            $this->getTaxRule(2)
        ];

        $this->taxRuleRepository
            ->expects($this->once())
            ->method('findByCountryAndRegion')
            ->with($address->getCountry(), $address->getRegion(), $address->getRegionText())
            ->willReturn($regionTaxRules);

        $this->assertEquals($regionTaxRules, $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $regionText = 'Alaska';
        return [
            [
                'country' => $country,
                'region' => $region,
                'regionText' => '',
            ],
            [
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
            ],
        ];
    }

    /**
     * @dataProvider matchWithoutNeededDataProvider
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     */
    public function testMatchWithoutNeededData($country, $region, $regionText)
    {
        $address = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->countryMatcher
            ->expects($this->never())
            ->method('match');

        $this->assertEquals([], $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchWithoutNeededDataProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $regionText = 'Alaska';

        return [
            'Without country' => [
                'country' => null,
                'region' => $region,
                'regionText' => $regionText
            ],
            'Without region and region text' => [
                'country' => $country,
                'region' => null,
                'regionText' => ''
            ],
        ];
    }
}
