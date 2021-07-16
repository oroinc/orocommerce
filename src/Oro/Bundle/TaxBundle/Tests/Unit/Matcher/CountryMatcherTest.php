<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class CountryMatcherTest extends AbstractMatcherTest
{
    /**
     * @var CountryMatcher
     */
    protected $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new CountryMatcher($this->doctrineHelper, self::TAX_RULE_CLASS);
    }

    /**
     * @dataProvider matchProvider
     * @param TaxRule[] $expected
     * @param Country $country
     * @param string $productTaxCode
     * @param string $customerTaxCode
     * @param TaxRule[] $taxRules
     */
    public function testMatch($expected, $country, $productTaxCode, $customerTaxCode, $taxRules)
    {
        $address = (new Address())
            ->setCountry($country);

        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($customerTaxCode) {
            $taxCodes[] = TaxCode::create($customerTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
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
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => $taxRules,
            ],
            'address without country' => [
                'expected' => [],
                'country' => null,
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => [],
            ],
            'address without product tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => null,
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'taxRules' => [],
            ],
            'address without customer tax code' => [
                'expected' => [],
                'country' => new Country('US'),
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => null,
                'taxRules' => [],
            ],
        ];
    }
}
