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

    /**
     * @var Region|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $region;

    /**
     * @var Country|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $country;

    /**
     * @var PaymentMethodsConfigsRuleDestination
     */
    protected $paymentMethodsConfigsRuleDestination;

    protected function setUp(): void
    {
        $this->country = $this->createMockCountry();
        $this->region = $this->createMockRegion();
        $this->paymentMethodsConfigsRuleDestination = $this->getEntity(
            PaymentMethodsConfigsRuleDestination::class,
            [
                'region' => $this->region,
                'country' => $this->country,
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
     *
     * @param array $data
     * @param string $expectedString
     */
    public function testToString(array $data, $expectedString)
    {
        $entity = (string)$this->getEntity(
            PaymentMethodsConfigsRuleDestination::class,
            $data
        );
        $this->assertEquals($expectedString, $entity);
    }

    /**
     * @return array
     */
    public function toStringDataProvider()
    {
        return [
            'all' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => $this->createMockRegion(),
                    'postalCodes' => $this->createMockPostalCodes(['12345']),
                ],
                'expectedString' => 'RegionName, CountryName 12345'
            ],
            'country and postal code' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => null,
                    'postalCodes' => $this->createMockPostalCodes(['12345', '54321']),
                ],
                'expectedString' => 'CountryName 12345, 54321'
            ],
            'country and region' => [
                'data' => [
                    'country' => $this->createMockCountry('SecondCountryName'),
                    'region' => $this->createMockRegion('SecondRegionName'),
                ],
                'expectedString' => 'SecondRegionName, SecondCountryName'
            ],
            'only country' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => null,
                ],
                'expectedString' => 'CountryName'
            ]
        ];
    }

    /**
     * @param string $name
     * @param string $iso2
     * @param string $iso3
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockCountry($name = 'CountryName', $iso2 = 'CountryIso2', $iso3 = 'CountryIso3')
    {
        $result = $this->getMockBuilder(Country::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getIso2Code')
            ->will($this->returnValue($iso2));
        $result->expects($this->any())
            ->method('getIso3Code')
            ->will($this->returnValue($iso3));

        return $result;
    }

    /**
     * @param string $name
     * @param string $code
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockRegion($name = 'RegionName', $code = 'RegionCode')
    {
        $result = $this->getMockBuilder(Region::class)->setConstructorArgs(['combinedCode'])->getMock();
        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($code));

        return $result;
    }

    /**
     * @param array $names
     * @return ArrayCollection|\PHPUnit\Framework\MockObject\MockObject[]
     */
    protected function createMockPostalCodes($names)
    {
        $results = new ArrayCollection();
        foreach ($names as $name) {
            $result = $this->getEntity(PaymentMethodsConfigsRuleDestinationPostalCode::class, ['name' => $name]);
            $results->add($result);
        }

        return $results;
    }
}
