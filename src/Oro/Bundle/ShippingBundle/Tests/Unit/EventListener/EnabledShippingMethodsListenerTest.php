<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\EventListener\EnabledShippingMethodsListener;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;

class EnabledShippingMethodsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var EnabledShippingMethodsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->listener = new EnabledShippingMethodsListener(
            $this->shippingMethodProvider
        );
    }

    private function getShippingMethod(string $identifier, int $sortOrder, bool $enabled): ShippingMethodStub
    {
        $method = new ShippingMethodStub();
        $method->setIdentifier($identifier);
        $method->setSortOrder($sortOrder);
        $method->setIsEnabled($enabled);

        return $method;
    }

    /**
     * @dataProvider getApplicableMethodsViewsProvider
     */
    public function testGetApplicableMethodsViews(array $methods, array $methodViews, array $expectedMethodViews)
    {
        $methodViewCollection = new ShippingMethodViewCollection();
        foreach ($methodViews as $id => $view) {
            $methodViewCollection ->addMethodView($id, $view);
        }

        $expectedCollection = new ShippingMethodViewCollection();
        foreach ($expectedMethodViews as $id => $view) {
            $expectedCollection ->addMethodView($id, $view);
        }

        $this->shippingMethodProvider->expects($this->any())
            ->method('getShippingMethod')
            ->willReturnCallback(function ($methodId) use ($methods) {
                return $methods[$methodId] ?? null;
            });

        $event = new ApplicableMethodsEvent($methodViewCollection, new \stdClass());
        $this->listener->removeDisabledShippingMethodViews($event);

        $this->assertEquals($expectedCollection, $event->getMethodCollection());
    }

    public function getApplicableMethodsViewsProvider(): array
    {
        return [
            'all_methods_enabled' => [
                'methods' => [
                    'flat_rate' => $this->getShippingMethod('flat_rate', 1, true),
                    'ups' => $this->getShippingMethod('ups', 2, true)
                ],
                'method_views' => [
                    'flat_rate' => ['flat_rate', false, 'flat_rate', 1],
                    'ups' => ['ups', false, 'ups', 2],
                ],
                'expected_method_views' => [
                    'flat_rate' => ['flat_rate', false, 'flat_rate', 1],
                    'ups' => ['ups', false, 'ups', 2],
                ]
            ],
            'all_methods_disabled' => [
                'methods' => [
                    'flat_rate' => $this->getShippingMethod('flat_rate', 1, false),
                    'ups' => $this->getShippingMethod('ups', 2, false)
                ],
                'method_views' => [
                    'flat_rate' => ['flat_rate', false, 'flat_rate', 1],
                    'ups' => ['ups', false, 'ups', 2],
                ],
                'expected_method_views' => []
            ],
            'some_methods_enabled' => [
                'methods' => [
                    'flat_rate' => $this->getShippingMethod('flat_rate', 1, true),
                    'ups' => $this->getShippingMethod('ups', 2, false)
                ],
                'method_views' => [
                    'flat_rate' => ['flat_rate', false, 'flat_rate', 1],
                    'ups' => ['ups', false, 'ups', 2],
                ],
                'expected_method_views' => [
                    'flat_rate' => ['flat_rate', false, 'flat_rate', 1],
                ]
            ],
            'no_methods' => [
                'methods' => [],
                'method_views' => [],
                'expected_method_views' => []
            ]
        ];
    }
}
