<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\RegionMatcher;
use Oro\Bundle\TaxBundle\Matcher\ZipCodeMatcher;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Component\Testing\ReflectionUtil;

class ZipCodeMatcherTest extends \PHPUnit\Framework\TestCase
{
    private const POSTAL_CODE = '02097';

    /** @var TaxRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $taxRuleRepository;

    /** @var RegionMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $regionMatcher;

    /** @var ZipCodeMatcher */
    private $matcher;

    protected function setUp(): void
    {
        $this->taxRuleRepository = $this->createMock(TaxRuleRepository::class);
        $this->regionMatcher = $this->createMock(RegionMatcher::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(TaxRule::class)
            ->willReturn($this->taxRuleRepository);

        $this->matcher = new ZipCodeMatcher($doctrine, $this->regionMatcher);
    }

    private function getTaxRule(int $id): TaxRule
    {
        $taxRule = new TaxRule();
        ReflectionUtil::setId($taxRule, $id);

        return $taxRule;
    }

    /**
     * @dataProvider matchProvider
     *
     * @param string|null  $productTaxCode
     * @param string|null  $customerTaxCode
     * @param Country|null $country
     * @param Region|null  $region
     * @param string       $regionText
     * @param TaxRule[]    $regionMatcherRules
     * @param TaxRule[]    $zipCodeMatcherTaxRules
     * @param TaxRule[]    $expected
     */
    public function testMatch(
        ?string $productTaxCode,
        ?string $customerTaxCode,
        ?Country $country,
        ?Region $region,
        string $regionText,
        array $regionMatcherRules,
        array $zipCodeMatcherTaxRules,
        array $expected
    ) {
        $address = (new Address())
            ->setPostalCode(self::POSTAL_CODE)
            ->setCountry($country)
            ->setRegion($region)
            ->setRegionText($regionText);

        $this->regionMatcher->expects($this->atLeastOnce())
            ->method('match')
            ->with($address)
            ->willReturn($regionMatcherRules);

        $taxCodes = [];
        if ($productTaxCode) {
            $taxCodes[] = TaxCode::create($productTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }
        if ($customerTaxCode) {
            $taxCodes[] = TaxCode::create($customerTaxCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        $taxCodes = TaxCodes::create($taxCodes);
        $isCallFindByCountryAndTaxCode = $country && ($region || $regionText) && $taxCodes->isFullFilledTaxCode();

        $this->taxRuleRepository->expects($isCallFindByCountryAndTaxCode ? $this->once() : $this->never())
            ->method('findByZipCodeAndTaxCode')
            ->with($taxCodes, self::POSTAL_CODE, $country, $region, $regionText)
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
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => '',
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'with regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => null,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => $zipCodeMatcherTaxRules,
                'expected' => $zipCodeMatcherTaxRules,
            ],
            'without country' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => null,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without product tax code' => [
                'productTaxCode' => null,
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without customer tax code' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => null,
                'country' => $country,
                'region' => $region,
                'regionText' => $regionText,
                'regionMatcherRules' => $regionMatcherTaxRules,
                'zipCodeMatcherRules' => [],
                'expected' => $regionMatcherTaxRules,
            ],
            'without region and regionText' => [
                'productTaxCode' => 'PRODUCT_TAX_CODE',
                'customerTaxCode' => 'ACCOUNT_TAX_CODE',
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
