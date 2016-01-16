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

    public function testMatch()
    {
        $address = new Address();
        $address->setCountry(new Country('US'));
        $address->setRegion(new Region('US-NY'));

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

    public function testMatchWithRegionText()
    {
        $address = new Address();
        $address->setCountry(new Country('US'));
        $address->setRegionText('US-region');

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
}
