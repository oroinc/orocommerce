<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Matcher\AddressMatcherRegistry;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Matcher\ResolvableMatcher;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

class ResolvableMatcherTest extends AbstractMatcherTest
{
    /** @var ResolvableMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $resolvableMatcher;

    /** @var CountryMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $countryMatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->countryMatcher = $this->createMock(CountryMatcher::class);

        $addressMatcherRegistry = $this->createMock(AddressMatcherRegistry::class);
        $addressMatcherRegistry->expects($this->once())
            ->method('getMatcherByType')
            ->with('test_granularity')
            ->willReturn($this->countryMatcher);

        $addressResolverSettingsProvider = $this->createMock(AddressResolverSettingsProvider::class);
        $addressResolverSettingsProvider->expects($this->once())
            ->method('getAddressResolverGranularity')
            ->willReturn('test_granularity');

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
