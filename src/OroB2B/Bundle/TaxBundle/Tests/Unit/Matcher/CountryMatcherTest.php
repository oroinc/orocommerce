<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;

class CountryMatcherTest extends AbstractMatcherTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new CountryMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);
    }

    /**
     * @dataProvider matchProvider
     * @param TaxRule[] $expected
     * @param Country $country
     * @param TaxRule[] $taxRules
     */
    public function testMatch($expected, $country, $taxRules)
    {
        $address = (new Address())
            ->setCountry($country);

        $this->taxRuleRepository
            ->expects(empty($expected) ? $this->never() : $this->once())
            ->method('findByCountry')
            ->with($country)
            ->willReturn($taxRules);

        $this->assertEquals($expected, $this->matcher->match($address));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        $taxRules = [
            new TaxRule(),
            new TaxRule(),
        ];

        return [
            'address with country' => [
                'expected' => $taxRules,
                'country' => new Country('US'),
                'taxRules' => $taxRules
            ],
            'address without country' => [
                'expected' => [],
                'country' => null,
                'taxRules' => $taxRules
            ]
        ];
    }
}
