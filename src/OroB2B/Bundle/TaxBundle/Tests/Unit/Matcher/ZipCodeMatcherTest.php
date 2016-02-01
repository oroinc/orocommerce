<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\RegionMatcher;
use OroB2B\Bundle\TaxBundle\Matcher\ZipCodeMatcher;

class ZipCodeMatcherTest extends AbstractMatcherTest
{
    const POSTAL_CODE = '02097';

    /**
     * @var RegionMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $regionMatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new ZipCodeMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);

        $this->regionMatcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Matcher\RegionMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher->setRegionMatcher($this->regionMatcher);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->regionMatcher);
    }

    /**
     * @dataProvider matchProvider
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @param TaxRule[] $regionMatcherRules
     * @param TaxRule[] $zipCodeMatcherTaxRules
     * @param TaxRule[] $expected
     */
    public function testMatch($country, $region, $regionText, $regionMatcherRules, $zipCodeMatcherTaxRules, $expected)
    {
        $address = (new Address())
            ->setPostalCode(self::POSTAL_CODE)
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->regionMatcher
            ->expects($this->once())
            ->method('match')
            ->with($address)
            ->willReturn($regionMatcherRules);

        $this->taxRuleRepository
            ->expects(empty($zipCodeMatcherTaxRules) ? $this->never() : $this->once())
            ->method('findByZipCode')
            ->with(
                self::POSTAL_CODE,
                $country,
                $region,
                $regionText
            )
            ->willReturn($zipCodeMatcherTaxRules);

        $this->assertEquals($expected, $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        $country = new Country('US');
        $region = new Region('US-NY');
        $regionText = 'Alaska';

        $regionMatcherTaxRules = [
            $this->getTaxRule(1),
        ];

        $zipCodeMatcherTaxRules = [
            $this->getTaxRule(1),
            $this->getTaxRule(2),
        ];

        return [
            'with region' => [
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'with regionText' => [
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'without country' => [
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without region and regionText' => [
                'country' => $country,
                'region' => null,
                'regionText' => '',
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
        ];
    }
}
