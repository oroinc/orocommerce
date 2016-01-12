<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;

class CountryMatcherTest extends \PHPUnit_Framework_TestCase
{
    const TAX_RULE_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\TaxRule';

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CountryMatcher
     */
    protected $matcher;

    /**
     * @var TaxRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxRuleRepository;

    protected function setUp()
    {
        $this->taxRuleRepository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(self::TAX_RULE_CLASS)
            ->willReturn($this->taxRuleRepository);

        $this->matcher = new CountryMatcher($this->doctrineHelper);
        $this->matcher->setTaxRuleClass(self::TAX_RULE_CLASS);
    }

    protected function tearDown()
    {
        unset($this->matcher, $this->doctrineHelper);
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
