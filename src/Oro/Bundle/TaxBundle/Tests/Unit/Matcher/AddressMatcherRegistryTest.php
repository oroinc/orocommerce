<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\TaxBundle\Matcher\AddressMatcherRegistry;

class AddressMatcherRegistryTest extends \PHPUnit_Framework_TestCase
{
    const REGION = 'region';
    const COUNTRY = 'country';

    /**
     * @var AddressMatcherRegistry
     */
    protected $matcherRegistry;

    public function setUp()
    {
        $this->matcherRegistry = new AddressMatcherRegistry();
    }

    public function testAddGetMatcher()
    {
        $regionMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\RegionMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcherRegistry->addMatcher(self::REGION, $regionMatcher);
        $storedMatchers = $this->matcherRegistry->getMatchers();
        $this->equalTo(1, count($storedMatchers));
        $this->assertEquals($regionMatcher, $storedMatchers[self::REGION]);
    }

    public function testGetMatcherByType()
    {
        $countryMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->matcherRegistry->addMatcher(self::COUNTRY, $countryMatcher);

        $regionMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\RegionMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcherRegistry->addMatcher(self::REGION, $regionMatcher);
        $storedMatchers = $this->matcherRegistry->getMatchers();

        $this->assertEquals(2, count($storedMatchers));

        $this->assertEquals($countryMatcher, $this->matcherRegistry->getMatcherByType(self::COUNTRY));
        $this->assertEquals($regionMatcher, $this->matcherRegistry->getMatcherByType(self::REGION));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Address Matcher for type "country" is missing.
     */
    public function testGetMatcherByTypeWithEmptyMatchers()
    {
        $this->assertEquals([], $this->matcherRegistry->getMatchers());

        $this->matcherRegistry->getMatcherByType(self::COUNTRY);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Address Matcher for type "region" is missing. Registered address matchers are "country"
     */
    public function testGetMatcherByTypeWithoutMatching()
    {
        $countryMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->matcherRegistry->addMatcher(self::COUNTRY, $countryMatcher);

        $this->matcherRegistry->getMatcherByType(self::REGION);
    }
}
