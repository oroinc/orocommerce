<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\RegionMatcher;
use Oro\Bundle\TaxBundle\Matcher\ZipCodeMatcher;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

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

        $this->regionMatcher = $this->getMockBuilder('Oro\Bundle\TaxBundle\Matcher\RegionMatcher')
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
     * @param string $productTaxCode
     * @param string $accountTaxCode
     * @param Country $country
     * @param Region $region
     * @param string $regionText
     * @param TaxRule[] $regionMatcherRules
     * @param TaxRule[] $zipCodeMatcherTaxRules
     * @param TaxRule[] $expected
     */
    public function testMatch(
        $productTaxCode,
        $accountTaxCode,
        $country,
        $region,
        $regionText,
        $regionMatcherRules,
        $zipCodeMatcherTaxRules,
        $expected
    ) {
        $address = (new Address())
            ->setPostalCode(self::POSTAL_CODE)
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->regionMatcher
            ->expects($this->atLeastOnce())
            ->method('match')
            ->with($address)
            ->willReturn($regionMatcherRules);

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
            ->method('findByZipCodeAndTaxCode')
            ->with(
                $taxCodes,
                self::POSTAL_CODE,
                $country,
                $region,
                $regionText
            )
            ->willReturn($zipCodeMatcherTaxRules);

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
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'with regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'without country' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without product tax code' => [
                'productTaxCode' => null,
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without account tax code' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => null,
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without region and regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
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
