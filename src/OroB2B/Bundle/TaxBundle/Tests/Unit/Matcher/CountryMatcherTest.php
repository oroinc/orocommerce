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

    public function testMatch()
    {
        $address = new Address();
        $address->setCountry(new Country('US'));

        $taxRules = [
            new TaxRule(),
            new TaxRule(),
        ];

        $this->taxRuleRepository
            ->expects($this->once())
            ->method('findByCountry')
            ->with($address->getCountry())
            ->willReturn($taxRules);

        $this->assertEquals($taxRules, $this->matcher->match($address));
    }
}
