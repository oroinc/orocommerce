<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\ShippingMethodsDataProvider;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
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

    /**
     * @var LayoutContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

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

        $this->context = $this
            ->getMockBuilder('Oro\Component\Layout\LayoutContext')
            ->disableOriginalConstructor()
            ->getMock();

        $contextData = $this
            ->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $contextData->expects($this->any())
            ->method('has')
            ->willReturn(true);
        $contextData->expects($this->any())
            ->method('get')
            ->willReturn((new Checkout()));

        $this->context->expects($this->any())
            ->method('data')
            ->willReturn($contextData);

        $this->provider = new ShippingMethodsDataProvider($this->registry, $this->shippingRulesProvider);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(ShippingMethodsDataProvider::NAME, $this->provider->getIdentifier());
    }

    public function testGetDataEmpty()
    {
        $this->shippingRulesProvider->expects($this->any())->method('getApplicableShippingRules')->willReturn([]);

        $data = $this->provider->getData($this->context);
        $this->assertEmpty($data);
    }

    public function testGetData()
    {
        $shippingConfig = new FlatRateRuleConfiguration();
        $shippingConfig->setMethod('flat_rate')
            ->setType('per_order')
            ->setPrice((new Price()));

        $shippingRule = new ShippingRule();
        $shippingRule->setName('TetsRule')
            ->setPriority(10)
            ->addConfiguration($shippingConfig);

        $this->shippingRulesProvider->expects($this->any())
            ->method('getApplicableShippingRules')
            ->willReturn([$shippingRule]);

        $method = $this->getMock('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface');
        $method->expects($this->once())->method('getLabel')->willReturn('label');
        $method->expects($this->once())->method('getShippingTypeLabel')->willReturn('typeLabel');
        $method->expects($this->once())->method('calculatePrice')->willReturn((new Price()));

        $this->registry->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($method);

        $data = $this->provider->getData($this->context);
        $expectedData = [
            'flat_rate' => [
                'label' => 'label',
                'name' => 'flat_rate',
                'types' => [
                    'per_order' => [
                        'label' => 'typeLabel',
                        'name'  => 'per_order',
                        'price' => new Price()
                    ]
                ]
            ]
        ];
        $this->assertEquals($expectedData, $data);
    }
}
