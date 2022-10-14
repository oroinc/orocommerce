<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentMethodsConfigsRuleDestinationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /** @var PaymentMethodsConfigsRuleDestination */
    private $paymentMethodsConfigsRuleDestination;

    protected function setUp(): void
    {
        $this->paymentMethodsConfigsRuleDestination = $this->getEntity(
            PaymentMethodsConfigsRuleDestination::class,
            [
                'region' => $this->getRegion(),
                'country' => $this->getCountry(),
                'postalCodes' => [new PaymentMethodsConfigsRuleDestinationPostalCode()],
            ]
        );
    }

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['methodsConfigsRule', new PaymentMethodsConfigsRule()],
            ['region', new Region('code')],
            ['regionText', 'text'],
            ['country', new Country('UA')],
        ];

        $entity = new PaymentMethodsConfigsRuleDestination();

        $this->assertPropertyAccessors($entity, $properties);
        $this->assertPropertyCollection($entity, 'postalCodes', new PaymentMethodsConfigsRuleDestinationPostalCode());
    }

    public function testGetRegionName()
    {
        $this->assertEquals('RegionName', $this->paymentMethodsConfigsRuleDestination->getRegionName());
        $this->paymentMethodsConfigsRuleDestination->setRegion(null);
        $this->assertEquals('', $this->paymentMethodsConfigsRuleDestination->getRegionName());
    }

    public function testGetRegionCode()
    {
        $this->assertEquals('RegionCode', $this->paymentMethodsConfigsRuleDestination->getRegionCode());
        $this->paymentMethodsConfigsRuleDestination->setRegion(null);
        $this->assertEquals('', $this->paymentMethodsConfigsRuleDestination->getRegionCode());
    }

    public function testGetCountryName()
    {
        $this->assertEquals('CountryName', $this->paymentMethodsConfigsRuleDestination->getCountryName());
        $this->paymentMethodsConfigsRuleDestination->setCountry(null);
        $this->assertEquals('', $this->paymentMethodsConfigsRuleDestination->getCountryName());
    }

    public function testGetCountryIso2()
    {
        $this->assertEquals('CountryIso2', $this->paymentMethodsConfigsRuleDestination->getCountryIso2());
        $this->paymentMethodsConfigsRuleDestination->setCountry(null);
        $this->assertEquals('', $this->paymentMethodsConfigsRuleDestination->getCountryIso2());
    }

    public function testGetCountryIso3()
    {
        $this->assertEquals('CountryIso3', $this->paymentMethodsConfigsRuleDestination->getCountryIso3());
        $this->paymentMethodsConfigsRuleDestination->setCountry(null);
        $this->assertEquals('', $this->paymentMethodsConfigsRuleDestination->getCountryIso3());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $data, string $expectedString)
    {
        $entity = (string)$this->getEntity(
            PaymentMethodsConfigsRuleDestination::class,
            $data
        );
        $this->assertEquals($expectedString, $entity);
    }

    public function toStringDataProvider(): array
    {
        return [
            'all' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => $this->getRegion(),
                    'postalCodes' => $this->getPostalCodes(['12345']),
                ],
                'expectedString' => 'RegionName, CountryName 12345'
            ],
            'country and postal code' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => null,
                    'postalCodes' => $this->getPostalCodes(['12345', '54321']),
                ],
                'expectedString' => 'CountryName 12345, 54321'
            ],
            'country and region' => [
                'data' => [
                    'country' => $this->getCountry('SecondCountryName'),
                    'region' => $this->getRegion('SecondRegionName'),
                ],
                'expectedString' => 'SecondRegionName, SecondCountryName'
            ],
            'only country' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => null,
                ],
                'expectedString' => 'CountryName'
            ]
        ];
    }

    private function getCountry(
        string $name = 'CountryName',
        string $iso2 = 'CountryIso2',
        string $iso3 = 'CountryIso3'
    ): Country {
        $result = $this->createMock(Country::class);
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getIso2Code')
            ->willReturn($iso2);
        $result->expects($this->any())
            ->method('getIso3Code')
            ->willReturn($iso3);

        return $result;
    }

    private function getRegion(string $name = 'RegionName', string $code = 'RegionCode'): Region
    {
        $result = $this->getMockBuilder(Region::class)
            ->setConstructorArgs(['combinedCode'])
            ->getMock();
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $result;
    }

    private function getPostalCodes(array $names): ArrayCollection
    {
        $results = new ArrayCollection();
        foreach ($names as $name) {
            $result = $this->getEntity(PaymentMethodsConfigsRuleDestinationPostalCode::class, ['name' => $name]);
            $results->add($result);
        }

        return $results;
    }
}
