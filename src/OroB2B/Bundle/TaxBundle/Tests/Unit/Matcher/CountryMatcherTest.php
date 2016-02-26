<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

class CountryMatcherTest extends AbstractMatcherTest
{
    /**
     * @var CountryMatcher
     */
    protected $matcher;

    protected function setUp()
    {
        parent::setUp();

        $this->matcher = new CountryMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);
    }

    /**
     * @dataProvider matchProvider
     * @param TaxRule[] $expected
     * @param Country $country
     * @param string $productTaxCode
     * @param string $accountTaxCode
     * @param TaxRule[] $taxRules
     */
    public function testMatch($expected, $country, $productTaxCode, $accountTaxCode, $taxRules)
    {
        $address = (new Address())
            ->setCountry($country);

        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($accountTaxCode) {
            $taxCodes[] = TaxCode::create($accountTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
        }


        $taxCodes = TaxCodes::create($taxCodes);

        $isCallFindByCountryAndTaxCode = $country && $taxCodes->isFullFilledTaxCode();

        $this->taxRuleRepository
            ->expects($isCallFindByCountryAndTaxCode ? $this->once() : $this->never())
            ->method('findByCountryAndTaxCode')
            ->with($taxCodes, $country)
            ->willReturn($taxRules);

        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));

        // cache
        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));
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
            'address with country and product tax code' => [
                'expected' => $taxRules,
                'country' => new Country('US'),
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => $taxRules,
            ],
            'address without country' => [
                'expected' => [],
                'country' => null,
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => [],
            ],
            'address without product tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => null,
                'accountTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => [],
            ],
            'address without account tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'accountTaxCode' => null,
                'taxRules' => [],
            ],
        ];
    }
}
