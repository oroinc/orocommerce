<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ShippingRulesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var DecoratedProductLineItemFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ShippingRulesProvider
     */
    protected $provider;

    public function setUp()
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

        $this->factory = $this->getMockBuilder(DecoratedProductLineItemFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->provider = new ShippingRulesProvider($doctrineHelper, $this->factory, $this->logger);
    }

    /**
     * @dataProvider getApplicableShippingRulesProvider
     *
     * @param ShippingContextInterface $context
     * @param ShippingRule $shippingRule
     * @param bool $isApplicable
     */
    public function testGetApplicableShippingRules(
        ShippingContextInterface $context,
        ShippingRule $shippingRule,
        $isApplicable
    ) {
        $this->repository->expects(static::once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context->getCurrency(), $context->getShippingAddress()->getCountryIso2())
            ->willReturn([$shippingRule]);

        $result = $this->provider->getApplicableShippingRules($context);
        if ($isApplicable) {
            $this->assertCount(1, $result);
            $this->assertSame($shippingRule, reset($result));
        } else {
            $this->assertEmpty($result);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getApplicableShippingRulesProvider()
    {
        return [
            'applicable country' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US')
                        ])
                    ]
                ]),
                'isApplicable' => true,
            ],
            'applicable region' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'combinedCode' => 'US-CA',
                            'code' => 'CA',
                        ]),
                        'postalCode' => '90401',
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'EUR',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US'),
                            'region' => $this->getEntity(Region::class, [
                                'combinedCode' => 'US-CA',
                                'code' => 'CA',
                            ]),
                        ])
                    ]
                ]),
                'isApplicable' => true,
            ],
            'applicable postal code' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'combinedCode' => 'US-CA',
                            'code' => 'CA',
                        ]),
                        'postalCode' => '90401',
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US'),
                            'region' => $this->getEntity(Region::class, [
                                'combinedCode' => 'US-CA',
                                'code' => 'CA',
                            ]),
                            'postalCode' => '90402, 90401',
                        ])
                    ]
                ]),
                'isApplicable' => true,
            ],
            'not applicable  country' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'EUR',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('FR')
                        ])
                    ]
                ]),
                'isApplicable' => false,
            ],
            'not applicable region' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'combinedCode' => 'US-CA',
                            'code' => 'CA',
                        ]),
                        'postalCode' => '90401',
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US'),
                            'region' => $this->getEntity(Region::class, [
                                'combinedCode' => 'US-MI',
                                'code' => 'MI',
                            ]),
                        ])
                    ]
                ]),
                'isApplicable' => false,
            ],
            'not applicable postal code' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'combinedCode' => 'US-CA',
                            'code' => 'CA',
                        ]),
                        'postalCode' => '90401',
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US'),
                            'region' => $this->getEntity(Region::class, [
                                'combinedCode' => 'US-CA',
                                'code' => 'CA',
                            ]),
                            'postalCode' => '90402, 90403',
                        ])
                    ]
                ]),
                'isApplicable' => false,
            ],
            'condition' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'combinedCode' => 'US-CA',
                            'code' => 'CA',
                        ]),
                        'postalCode' => '90401',
                    ]),
                    ShippingContext::FIELD_BILLING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                    ShippingContext::FIELD_SHIPPING_ORIGIN => $this->getEntity(ShippingAddressStub::class, [
                        'region' => $this->getEntity(Region::class, [
                            'code' => 'CA',
                        ]),
                    ]),
                    ShippingContext::FIELD_SUBTOTAL => Price::create(1039.0, 'USD'),
                    ShippingContext::FIELD_PAYMENT_METHOD => 'integration_payment_method',
                    ShippingContext::FIELD_CUSTOMER => (new Account())->setName('Customer Name'),
                    ShippingContext::FIELD_CUSTOMER_USER => (new AccountUser())->setFirstName('First Name'),
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'conditions' => <<<'EXPRESSION'
currency = "USD"
and
count(lineItems) = 0
and
shippingAddress.postalCode = "90401"
and
billingAddress.country.iso2Code = "US"
and
shippingOrigin.region.code = "CA"
and
subtotal.value > 1000
and
paymentMethod = "integration_payment_method"
and
customer.name = "Customer Name"
and 
customerUser.firstName = "First Name"
EXPRESSION
                    ,
                    'destinations' => [
                        $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                            'country' => new Country('US'),
                            'region' => $this->getEntity(Region::class, [
                                'combinedCode' => 'US-CA',
                                'code' => 'CA',
                            ]),
                            'postalCode' => '90401',
                        ])
                    ]
                ]),
                'isApplicable' => true,
            ],
            'false condition' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'conditions' => 'currency = "EUR"',
                ]),
                'isApplicable' => false,
            ],
            'unknown parameter condition' => [
                'context' => $this->createShippingContext([
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_CURRENCY => 'USD',
                ]),
                'shippingRule' => $this->getEntity(ShippingRule::class, [
                    'conditions' => 'unknown = "value"',
                ]),
                'isApplicable' => false,
            ],
        ];
    }

    public function testGetApplicableShippingRulesMultipleRules()
    {
        $context = $this->createShippingContext([
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                'country' => new Country('US'),
                'region' => $this->getEntity(Region::class, [
                    'combinedCode' => 'US-CA',
                    'code' => 'CA',
                ]),
                'postalCode' => '90401',
            ]),
            ShippingContext::FIELD_CURRENCY => 'USD',
        ]);

        $shippingRule1 = $this->getEntity(ShippingRule::class, [
            'id' => 1,
            'destinations' => [
                $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                    'country' => new Country('US'),
                    'region' => $this->getEntity(Region::class, [
                        'combinedCode' => 'US-CA',
                        'code' => 'CA',
                    ]),
                    'postalCode' => '90402, 90401',
                ])
            ]
        ]);
        $shippingRule2 = $this->getEntity(ShippingRule::class, [
            'id' => 2,
            'destinations' => [
                $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                    'country' => new Country('FR'),
                ])
            ]
        ]);
        $shippingRule3 = $this->getEntity(ShippingRule::class, [
            'id' => 3,
            'destinations' => [
                $this->getEntity(ShippingMethodsConfigsRuleDestination::class, [
                    'country' => new Country('US'),
                    'region' => $this->getEntity(Region::class, [
                        'combinedCode' => 'US-CA',
                        'code' => 'CA',
                    ]),
                    'postalCode' => '90401',
                ])
            ]
        ]);

        $this->repository->expects(static::once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context->getCurrency(), $context->getShippingAddress()->getCountryIso2())
            ->willReturn([$shippingRule1, $shippingRule2, $shippingRule3]);

        $this->assertSame([$shippingRule1, $shippingRule3], $this->provider->getApplicableShippingRules($context));
    }

    public function testGetApplicableShippingRulesLogger()
    {
        $context = $this->createShippingContext([
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                'country' => new Country('US'),
            ]),
            ShippingContext::FIELD_CURRENCY => 'USD',
        ]);

        $this->repository->expects(static::once())
            ->method('getEnabledOrderedRulesByCurrencyAndCountry')
            ->with($context->getCurrency(), $context->getShippingAddress()->getCountryIso2())
            ->willReturn([
                $this->getEntity(ShippingRule::class, [
                    'id' => 20,
                    'conditions' => 'unknown = "value"',
                ])
            ]);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Shipping rule condition evaluation error: Undefined index: unknown', ['ShippingRule::$id' => 20]);

        $result = $this->provider->getApplicableShippingRules($context);
        $this->assertEmpty($result);
    }

    /**
     * @param array $params
     *
     * @return ShippingContext
     */
    private function createShippingContext(array $params)
    {
        $actualParams = array_merge([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([])
        ], $params);

        return new ShippingContext($actualParams);
    }
}
