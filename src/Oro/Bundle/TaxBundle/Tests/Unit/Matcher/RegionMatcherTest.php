<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Matcher\RegionMatcher;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

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

        $this->countryMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\CountryMatcher')
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
     * @param string $productTaxCode
     * @param string $accountTaxCode
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @param TaxRule[] $countryMatcherTaxRules
     * @param TaxRule[] $regionTaxRules
     * @param TaxRule[] $expected
     */
    public function testMatch(
        $productTaxCode,
        $accountTaxCode,
        $country,
        $region,
        $regionText,
        $countryMatcherTaxRules,
        $regionTaxRules,
        $expected
    ) {
        $address = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->countryMatcher
            ->expects($this->atLeastOnce())
            ->method('match')
            ->with($address)
            ->willReturn($countryMatcherTaxRules);

        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($accountTaxCode) {
            $taxCodes[] = TaxCode::create($accountTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        $taxCodes = TaxCodes::create($taxCodes);
        $isCallFindByCountryAndTaxCode = $country && ($region || $regionText) && $taxCodes->isFullFilledTaxCode();

        $this->taxRuleRepository
            ->expects($isCallFindByCountryAndTaxCode ? $this->once() : $this->never())
            ->method('findByRegionAndTaxCode')
            ->with($taxCodes, $country, $region, $regionText)
            ->willReturn($regionTaxRules);

        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));

        // cache
        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));
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
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => $regionTaxRules,
                'expected' => $regionTaxRules,
            ],
            'with country and regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => $regionTaxRules,
                'expected' => $regionTaxRules,
            ],
            'without product tax code' => [
                'productTaxCode' => null,
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without account tax code' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => null,
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without country' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'regionTaxRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without region and region text' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
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
