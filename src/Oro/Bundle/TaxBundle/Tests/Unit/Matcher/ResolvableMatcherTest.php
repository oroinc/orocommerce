<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Matcher\ResolvableMatcher;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class ResolvableMatcherTest extends AbstractMatcherTest
{
    /**
     * @var ResolvableMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolvableMatcher;

    /**
     * @var CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMatcher;

    public function setUp()
    {
        parent::setUp();

        $this->countryMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $addressMatcherRegistry = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\AddressMatcherRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $addressMatcherRegistry
            ->expects(static::once())
            ->method('getMatcherByType')
            ->will(static::returnValue($this->countryMatcher));

        $addressResolverSettingsProvider = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();
        
        $addressResolverSettingsProvider
            ->expects(static::once())
            ->method('getAddressResolverGranularity');

        $this->resolvableMatcher = new ResolvableMatcher($addressMatcherRegistry, $addressResolverSettingsProvider);
    }

    public function testMatch()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $regionText = 'Alaska';

        $address = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $productTaxCode = 'PRODUCT_TAX_CODE';
        $accountTaxCode = 'ACCOUNT_TAX_CODE';
        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($accountTaxCode) {
            $taxCodes[] = TaxCode::create($accountTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        $taxCodes = TaxCodes::create($taxCodes);

        $this->resolvableMatcher->match($address, $taxCodes);
    }
}
