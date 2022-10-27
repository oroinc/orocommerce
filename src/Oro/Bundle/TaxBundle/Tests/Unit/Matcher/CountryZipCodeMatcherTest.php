<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Matcher\CountryZipCodeMatcher;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class CountryZipCodeMatcherTest extends AbstractMatcherTest
{
    /** @var CountryMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $countryMatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->countryMatcher = $this->createMock(CountryMatcher::class);

        $this->matcher = new CountryZipCodeMatcher($this->doctrineHelper, TaxRule::class);
        $this->matcher->setCountryMatcher($this->countryMatcher);
    }

    /**
     * @dataProvider matchProvider
     *
     * @param string|null  $productTaxCode
     * @param string|null  $customerTaxCode
     * @param Country|null $country
     * @param Region|null  $region
     * @param string       $regionText
     * @param string|null  $zipCode
     * @param TaxRule[]    $countryMatcherTaxRules
     * @param TaxRule[]    $zipCodeMatcherTaxRules
     * @param TaxRule[]    $expected
     */
    public function testMatch(
        ?string $productTaxCode,
        ?string $customerTaxCode,
        ?Country $country,
        ?Region $region,
        string $regionText,
        ?string $zipCode,
        array $countryMatcherTaxRules,
        array $zipCodeMatcherTaxRules,
        array $expected
    ) {
        $address = (new Address())
            ->setPostalCode($zipCode)
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->countryMatcher->expects($this->atLeastOnce())
            ->method('match')
            ->with($address)
            ->willReturn($countryMatcherTaxRules);

        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($customerTaxCode) {
            $taxCodes[] = TaxCode::create($customerTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        $taxCodes = TaxCodes::create($taxCodes);
        $isCallFindByCountryAndTaxCode = $country && $zipCode && $taxCodes->isFullFilledTaxCode();

        $this->taxRuleRepository->expects($isCallFindByCountryAndTaxCode ? $this->once() : $this->never())
            ->method('findByCountryAndZipCodeAndTaxCode')
            ->with($taxCodes, $zipCode, $country)
            ->willReturn($zipCodeMatcherTaxRules);

        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));

        // cache
        $this->assertEquals($expected, $this->matcher->match($address, $taxCodes));
    }

    public function matchProvider(): array
    {
        $country = new Country('US');
        $region = new Region('US-NY');
        $regionText = 'Alaska';

        $countryMatcherTaxRules = [
            $this->getTaxRule(1),
        ];

        $zipCodeMatcherTaxRules = [
            $this->getTaxRule(1),
            $this->getTaxRule(2),
        ];

        return [
            'with region' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'with regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'without zip' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'zipCode' => null,
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without country' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without product tax code' => [
                'productTaxCode' => null,
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without customer tax code' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => null,
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $countryMatcherTaxRules,
            ],
            'without region and regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => null,
                'regionText' => '',
                'zipCode' => '02097',
                'countryMatcherTaxRules' => $countryMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
        ];
    }
}
