<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\ShippingMethodsDataProvider;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingRulesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingRulesProvider;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextProviderFactory;

    /**
     * @var ShippingMethodsDataProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingRulesProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ShippingMethodsDataProvider(
            $this->registry,
            $this->shippingRulesProvider,
            $this->shippingContextProviderFactory
        );
    }

    public function testGetMethodsEmpty()
    {
        $this->shippingRulesProvider->expects(static::any())->method('getApplicableShippingRules')->willReturn([]);

        $this->shippingContextProviderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ShippingContextProvider([]));

        $data = $this->provider->getMethods(new Checkout());
        static::assertEmpty($data);
    }

    public function testGetMethods()
    {
        /** @var FlatRateRuleConfiguration $shippingConfig */
        $shippingConfig = $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration',
            [
                'id'     => 1,
                'method' => 'flat_rate',
                'type'   => 'per_order',
            ]
        );

        $shippingRule = new ShippingRule();
        $shippingRule->setName('TetsRule')
            ->setPriority(10)
            ->addConfiguration($shippingConfig);

        $this->shippingRulesProvider->expects(static::any())
            ->method('getApplicableShippingRules')
            ->willReturn([10 => $shippingRule]);

        $method = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $method->expects(static::once())->method('getLabel')->willReturn('label');
        $method->expects(static::once())->method('getShippingTypeLabel')->willReturn('typeLabel');
        $method->expects(static::once())->method('calculatePrice')->willReturn(new Price());

        $this->registry->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn($method);

        $this->shippingContextProviderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ShippingContextProvider([]));

        $data = $this->provider->getMethods(new Checkout());
        $expectedData = [
            'flat_rate' => [
                'label' => 'label',
                'name' => 'flat_rate',
                'types' => [
                    'per_order' => [
                        'label' => 'typeLabel',
                        'name'  => 'per_order',
                        'price' => new Price(),
                        'shippingRuleConfig' => $shippingConfig->getId()
                    ]
                ]
            ]
        ];
        static::assertEquals($expectedData, $data);
    }
}
