<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\EnabledMethodsShippingPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\PriceAwareShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;

class EnabledMethodsShippingPriceProviderDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var ShippingPriceProviderInterface
     */
    protected $shippingPriceProviderInterface;

    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->shippingPriceProviderInterface = $this->createMock(ShippingPriceProviderInterface::class);
        $this->registry = $this->createMock(ShippingMethodRegistry::class);
    }

    public function testGetApplicableMethodsViews()
    {
        $shippingLineItems = [new ShippingLineItem([])];
        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($shippingLineItems),
            ShippingContext::FIELD_CURRENCY => 'USD'
        ]);

        $methodViewCollection = new ShippingMethodViewCollection();
        $methodViewCollection ->addMethodView('flat_rate', ['flat_rate', false, 'flat_rate', 1]);
        $methodViewCollection ->addMethodView('ups', ['ups', false, 'label_2', 2]);

        $this->shippingPriceProviderInterface->expects($this->any())
            ->method('getApplicableMethodsViews')
            ->with($context)
            ->willReturn($methodViewCollection);
        
        $decorator = new EnabledMethodsShippingPriceProviderDecorator(
            $this->shippingPriceProviderInterface,
            $this->registry
        );

        $methods = [
            'flat_rate' => $this->getEntity(ShippingMethodStub::class, [
                'identifier' => 'flat_rate',
                'sortOrder' => 1,
                'isEnabled' => true,
                'types' => [
                    'primary' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'primary',
                        'sortOrder' => 1,
                    ])
                ]
            ]),
            'ups' => $this->getEntity(PriceAwareShippingMethodStub::class, [
                'identifier' => 'ups',
                'sortOrder' => 2,
                'isEnabled' => false,
                'isGrouped' => true,
                'types' => [
                    'ground' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'ground',
                        'sortOrder' => 1,
                    ]),
                    'air' => $this->getEntity(ShippingMethodTypeStub::class, [
                        'identifier' => 'air',
                        'sortOrder' => 2,
                    ])
                ]
            ])
        ];
        $this->registry->expects($this->any())
            ->method('getShippingMethod')
            ->will($this->returnCallback(function ($methodId) use ($methods) {
                return array_key_exists($methodId, $methods) ? $methods[$methodId] : null;
            }));
        
        $expectedCollection = new ShippingMethodViewCollection();
        $expectedCollection->addMethodView('flat_rate', ['flat_rate', false, 'flat_rate', 1]);

        $this->assertEquals($expectedCollection, $decorator->getApplicableMethodsViews($context));
    }
}
