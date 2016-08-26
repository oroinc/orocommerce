<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\Address;

class ShippingRulesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var ShippingRulesProvider
     */
    protected $provider;

    protected function setUp()
    {
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ShippingRulesProvider($doctrineHelper);

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
    }

    /**
     * @dataProvider getApplicableShippingRulesProvider
     *
     * @param ShippingRule $shippingRule
     * @param array $context
     * @param bool $expectedApplicability
     */
    public function testGetApplicableShippingRules(
        ShippingRule $shippingRule,
        array $context,
        $expectedApplicability
    ) {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context['shippingAddress'];

        $this->repository->expects($this->once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context['currency'], $shippingAddress->getCountry())
            ->willReturn([$shippingRule]);

        $shippingContext = $this->getMock(ShippingContextAwareInterface::class);

        $shippingContext->expects($this->any())
            ->method('getShippingContext')
            ->willReturn($context);

        $rules = $this->provider->getApplicableShippingRules($shippingContext);

        if ($expectedApplicability) {
            $this->assertCount(1, $rules);
            $this->assertEquals($shippingRule, $rules[0]);
        } else {
            $this->assertEmpty($rules);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getApplicableShippingRulesProvider()
    {
        return [
            'condition and currency and empty destinations' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => 'true',
                    'currency' => 'USD',
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => true,
            ],
            'false condition' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => 'false',
                    'currency' => 'USD',
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => false,
            ],
            'country' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'US'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => true,
            ],
            'wrong country' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'FR'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => false,
            ],
            'several countries' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'FR'],
                        ['country' => 'US'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => true,
            ],
            'wrong several countries' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'FR'],
                        ['country' => 'TH'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'US'),
                'expectedApplicability' => false,
            ],
            'region' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'TH', 'region' => 'TH-83',]
                    ]
                ]),
                'context' => $this->createContext('USD', 'TH', 'TH-83'),
                'expectedApplicability' => true,
            ],
            'wrong region' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'TH', 'region' => 'TH-82'],
                    ]
                ]),
                'context' => $this->createContext('USD', 'TH', 'TH-83'),
                'expectedApplicability' => false,
            ],
            'postal code' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'TH', 'region' => 'TH-83', 'postalCode' => '12345']
                    ]
                ]),
                'context' => $this->createContext('USD', 'TH', 'TH-83', '12345'),
                'expectedApplicability' => true,
            ],
            'several postal codes' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'TH', 'region' => 'TH-83', 'postalCode' => '54321, 12345']
                    ]
                ]),
                'context' => $this->createContext('USD', 'TH', 'TH-83', '12345'),
                'expectedApplicability' => true,
            ],
            'wrong postal codes' => [
                'data' => $this->createShippingRule([
                    'name' => 'ShippingRule.1',
                    'conditions' => null,
                    'currency' => 'USD',
                    'destinations' => [
                        ['country' => 'TH', 'region' => 'TH-83', 'postalCode' => '54321, 12345']
                    ]
                ]),
                'context' => $this->createContext('USD', 'TH', 'TH-83', '12346'),
                'expectedApplicability' => false,
            ],
        ];
    }

    public function testGetApplicableShippingRulesMultiple()
    {
        $context = $this->createContext('USD', 'TH', 'TH-83');
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context['shippingAddress'];

        $firstShippingRule = $this->createShippingRule([
            'name' => 'ShippingRule.1',
            'conditions' => null,
            'currency' => 'USD',
            'destinations' => [
                ['country' => 'TH']
            ]
        ]);
        $secondShippingRule = $this->createShippingRule([
            'name' => 'ShippingRule.1',
            'conditions' => null,
            'currency' => 'USD',
            'destinations' => [
                ['country' => 'TH', 'region' => 'TH-83']
            ]
        ]);
        $this->repository->expects($this->once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context['currency'], $shippingAddress->getCountry())
            ->willReturn([$firstShippingRule, $secondShippingRule]);

        $shippingContext = $this->getMock(ShippingContextAwareInterface::class);

        $shippingContext->expects($this->any())
            ->method('getShippingContext')
            ->willReturn($context);

        $rules = $this->provider->getApplicableShippingRules($shippingContext);

        $this->assertCount(2, $rules);
        $this->assertEquals($firstShippingRule, $rules[0]);
        $this->assertEquals($secondShippingRule, $rules[1]);
    }

    public function testGetApplicableShippingRulesStopProcessing()
    {
        $context = $this->createContext('USD', 'TH');
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context['shippingAddress'];

        $firstShippingRule = $this->createShippingRule([
            'name' => 'ShippingRule.1',
            'conditions' => null,
            'currency' => 'USD',
            'stopProcessing' => true,
            'destinations' => [
                ['country' => 'TH']
            ]
        ]);
        $secondShippingRule = $this->createShippingRule([
            'name' => 'ShippingRule.1',
            'conditions' => null,
            'currency' => 'USD',
            'destinations' => [
                ['country' => 'TH']
            ]
        ]);
        $this->repository->expects($this->once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context['currency'], $shippingAddress->getCountry())
            ->willReturn([$firstShippingRule, $secondShippingRule]);

        $shippingContext = $this->getMock(ShippingContextAwareInterface::class);

        $shippingContext->expects($this->any())
            ->method('getShippingContext')
            ->willReturn($context);

        $rules = $this->provider->getApplicableShippingRules($shippingContext);

        $this->assertCount(1, $rules);
        $this->assertEquals($firstShippingRule, $rules[0]);
    }

    /**
     * @param string $currency
     * @param string $country
     * @param string|null $region
     * @param string|null $postalCode
     * @return array
     */
    protected function createContext($currency, $country, $region = null, $postalCode = null)
    {
        return [
            'currency' => $currency,
            'shippingAddress' => $this->getEntity(Address::class, [
                'country' => $this->getEntity(Country::class, ['iso2Code' => $country]),
                'region' => $region ? $this->getEntity(Region::class, ['code' => $region]) : null,
                'postalCode' => $postalCode,
            ]),
        ];
    }

    /**
     * @param array $data
     * @return ShippingRule
     */
    protected function createShippingRule(array $data)
    {
        if (array_key_exists('destinations', $data)) {
            $data['destinations'] = new ArrayCollection(array_map(function ($destinationData) {
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
        return $this->getEntity(ShippingRule::class, $data);
    }
}
