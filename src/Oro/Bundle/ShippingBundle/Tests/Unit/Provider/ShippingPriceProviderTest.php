<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\FlatRatePricesAwareShippingMethod;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingPriceProvider
     */
    protected $shippingPriceProvider;

    protected function setUp()
    {
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(ShippingRuleRepository::class)
            ->disableOriginalConstructor()->getMock();

        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroShippingBundle:ShippingRule')
            ->willReturn($this->repository);

        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with('OroShippingBundle:ShippingRule')
            ->willReturn($entityManager);

        $this->registry = $this->getMock(ShippingMethodRegistry::class);
        $this->shippingPriceProvider = new ShippingPriceProvider($doctrineHelper, $this->registry);
    }

    /**
     * @dataProvider getApplicableShippingRulesProvider
     *
     * @param ShippingRule $shippingRule
     * @param ShippingMethodInterface $shippingMethod
     * @param ShippingContext $context
     */
    public function testGetApplicableMethodsWithTypesData(
        ShippingRule $shippingRule,
        ShippingMethodInterface $shippingMethod,
        ShippingContext $context
    ) {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context->getShippingAddress();

        $this->registry->expects($this->any())
            ->method('getShippingMethod')
            ->with('flat_rate')
            ->willReturn($shippingMethod)
        ;

        $this->repository->expects($this->once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context->getCurrency(), $shippingAddress->getCountry())
            ->willReturn([$shippingRule])
        ;

        $applicableMethodsWithTypesData = $this->shippingPriceProvider->getApplicableMethodsWithTypesData($context);

        $this->assertCount(1, $applicableMethodsWithTypesData);
        $this->assertEquals('flat_rate', $applicableMethodsWithTypesData[0]['identifier']);
        $this->assertEquals('oro.shipping.method.flat_rate.label', $applicableMethodsWithTypesData[0]['label']);
        $this->assertCount(1, $applicableMethodsWithTypesData[0]['types']);
        $this->assertEquals('primary', $applicableMethodsWithTypesData[0]['types'][0]['identifier']);
        $this->assertInstanceOf(Price::class, $applicableMethodsWithTypesData[0]['types'][0]['price']);
        $this->assertEquals(
            Price::create(5, 'USD'),
            $applicableMethodsWithTypesData[0]['types'][0]['price']
        );
    }

    /**
     * @dataProvider getApplicableShippingRulesProvider
     *
     * @param ShippingRule $shippingRule
     * @param ShippingMethodInterface $shippingMethod
     * @param ShippingContext $context
     */
    public function testGetPrice(
        ShippingRule $shippingRule,
        ShippingMethodInterface $shippingMethod,
        ShippingContext $context
    ) {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context->getShippingAddress();

        $this->registry->expects($this->any())
            ->method('getShippingMethod')
            ->with('flat_rate')
            ->willReturn($shippingMethod)
        ;

        $this->repository->expects($this->once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context->getCurrency(), $shippingAddress->getCountry())
            ->willReturn([$shippingRule])
        ;

        $methodIdentifier = 'flat_rate';
        $typeIdentifier = 'primary';

        $price = $this->shippingPriceProvider->getPrice(
            $context,
            $methodIdentifier,
            $typeIdentifier
        );

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(Price::create(5, 'USD'), $price);
    }

    /**
     * @dataProvider destinationApplicableProvider
     *
     * @param ArrayCollection $destinations
     * @param ShippingContext $context
     * @param bool $expectedValue
     */
    public function testDestinationApplicable(ArrayCollection $destinations, ShippingContext $context, $expectedValue)
    {
        $destinationApplicableReflection = self::getMethod('destinationApplicable');
        $result = $destinationApplicableReflection->invokeArgs($this->shippingPriceProvider, [$destinations, $context]);

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @dataProvider getPricesAwareShippingMethodTypePricesProvider
     *
     * @param ShippingContext $context
     * @param ShippingMethodInterface $method
     * @param string|int|null $methodTypeIdentifier
     * @param Price $expectedValue
     */
    public function testGetPricesAwareShippingMethodTypePrices(
        ShippingContext $context,
        ShippingMethodInterface $method,
        $methodTypeIdentifier,
        $expectedValue
    ) {
        $awareShippingMethodTypePricesReflection = self::getMethod('getAwareShippingMethodTypePrices');
        $prices = $awareShippingMethodTypePricesReflection->invokeArgs(
            $this->shippingPriceProvider,
            [$context, $method, $methodTypeIdentifier]
        );

        $this->assertCount(1, $prices);

        $price = reset($prices);

        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals($expectedValue->getValue(), $price->getValue());
    }

    /**
     * @param string $name
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new \ReflectionClass(ShippingPriceProvider::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param array $data
     * @return ShippingRule
     */
    protected function createShippingRule(array $data)
    {
        $data['destinations'] = $this->createDestinations($data);
        $data['methodConfigs'] = [$this->getEntity(ShippingRuleMethodConfig::class, ['method' => 'flat_rate'])];

        return $this->getEntity(ShippingRule::class, $data);
    }

    /**
     * @param array $data
     * @return Collection|ShippingRuleDestination[]|array
     */
    protected function createDestinations(array $data)
    {
        if (array_key_exists('destinations', $data)) {
            return new ArrayCollection(array_map(function ($destinationData) {
                $region = null;
                if (array_key_exists('region', $destinationData)) {
                    $region = $this->getEntity(Region::class, ['code' => $destinationData['region']]);
                }
                $postalCode = null;
                if (array_key_exists('postalCode', $destinationData)) {
                    $postalCode = $destinationData['postalCode'];
                }
                return $this->getEntity(ShippingRuleDestination::class, [
                    'country' => $this->getEntity(Country::class, ['iso2Code' => $destinationData['country']]),
                    'region' => $region,
                    'postalCode' => $postalCode,
                ]);
            }, $data['destinations']));
        }

        return new ArrayCollection();
    }

    /**
     * @param Price $price
     * @return ShippingMethodInterface
     */
    protected function createShippingMethodWithType(Price $price)
    {
        $shippingMethodType = $this->getMockBuilder(FlatRateShippingMethodType::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $shippingMethodType->expects($this->any())
            ->method('calculatePrice')
            ->willReturn($price)
        ;
        $shippingMethodType->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('primary')
        ;
        $shippingMethodType->expects($this->any())
            ->method('getOptions')
            ->willReturn([])
        ;

        return $this->getEntity(FlatRatePricesAwareShippingMethod::class, ['type' => $shippingMethodType]);
    }

    /**
     * @param string $currency
     * @param string $country
     * @param string|null $region
     * @param string|null $postalCode
     * @return ShippingContext
     */
    protected function createContext($currency, $country, $region = null, $postalCode = null)
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => $currency,
            'shippingAddress' => $this->getEntity(
                Address::class,
                [
                'country' => $this->getEntity(Country::class, ['iso2Code' => $country]),
                'region' => $region ? $this->getEntity(Region::class, ['code' => $region]) : null,
                'postalCode' => $postalCode,
                ]
            )
        ]);

        return $context;
    }

    /**
     * @return array
     */
    public function getApplicableShippingRulesProvider()
    {
        return [
            'data' => [
                'shippingRule' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => 'true',
                    'currency' => 'USD',
                ]),
                'shippingMethod' => $this->createShippingMethodWithType(Price::create(5, 'USD')),
                'context' => $this->createContext('USD', 'US'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function destinationApplicableProvider()
    {
        return [
            'applicable country' => [
                'destinations' => $this->createDestinations([
                    'destinations' => [
                        ['country' => 'US']
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedValue' => true,
            ],
            'not applicable country' => [
                'destinations' => $this->createDestinations([
                    'destinations' => [
                        ['country' => 'FR']
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedValue' => false,
            ],
            'several applicable country' => [
                'destinations' => $this->createDestinations([
                    'destinations' => [
                        ['country' => 'FR'],
                        ['country' => 'US'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedValue' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPricesAwareShippingMethodTypePricesProvider()
    {
        return [
            'without type identifier' => [
                'context' => $this->createContext('USD', 'US'),
                'method' => $this->createShippingMethodWithType(Price::create(5, 'USD')),
                'methodTypeIdentifier' => null,
                'expectedValue' => Price::create(5, 'USD'),
            ],
            'with type identifier' => [
                'context' => $this->createContext('USD', 'US'),
                'method' => $this->createShippingMethodWithType(Price::create(5, 'USD')),
                'methodTypeIdentifier' => 'primary',
                'expectedValue' => Price::create(5, 'USD'),
            ],
        ];
    }
}
