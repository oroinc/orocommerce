<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;

class ShippingMethodChoicesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodChoicesProvider */
    private $choicesProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->choicesProvider = new ShippingMethodChoicesProvider($this->shippingMethodProvider);
    }

    private function getShippingMethod(
        string $identifier,
        int $sortOrder,
        string $label,
        bool $enabled
    ): ShippingMethodStub {
        $shippingMethod = new ShippingMethodStub();
        $shippingMethod->setIdentifier($identifier);
        $shippingMethod->setSortOrder($sortOrder);
        $shippingMethod->setLabel($label);
        $shippingMethod->setIsEnabled($enabled);

        return $shippingMethod;
    }

    private function getShippingMethodType(
        string $identifier,
        int $sortOrder,
        string $label
    ): ShippingMethodTypeStub {
        $shippingMethodType = new ShippingMethodTypeStub();
        $shippingMethodType->setIdentifier($identifier);
        $shippingMethodType->setSortOrder($sortOrder);
        $shippingMethodType->setLabel($label);

        return $shippingMethodType;
    }

    public function testGetMethods(): void
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'flat_rate' => $this->getShippingMethod('flat_rate', 1, 'flat rate', true),
                'disabled'  => $this->getShippingMethod('disabled', 2, 'disabled', false),
                'ups'       => $this->getShippingMethod('ups', 3, 'ups', true)
            ]);

        $this->assertEquals(
            [
                'flat rate' => 'flat_rate',
                'ups'       => 'ups'
            ],
            $this->choicesProvider->getMethods()
        );
    }

    public function testGetMethodsWhenShippingMethodProviderDoesNotReturnShippingMethods(): void
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([]);

        $this->assertEquals([], $this->choicesProvider->getMethods());
    }

    public function testGetMethodsWhenNoOptionsConfigurationForm(): void
    {
        $method1 = $this->getShippingMethod('method1', 1, 'method 1', true);
        $method1->setOptionsConfigurationFormType('');

        $method2 = $this->getShippingMethod('method2', 2, 'method 2', true);

        $method3 = $this->getShippingMethod('method3', 3, 'method 3', true);
        $method3->setTypes([$this->getShippingMethodType('type1', 1, 'type 1')]);

        $method4 = $this->getShippingMethod('method4', 4, 'method 4', true);
        $method4->setTypes([
            $this->getShippingMethodType('type1', 1, 'type 1'),
            $this->getShippingMethodType('type2', 2, 'type 2')
        ]);
        $method4->getType('type1')->setOptionsConfigurationFormType('');
        $method4->getType('type2')->setOptionsConfigurationFormType('');

        $method5 = $this->getShippingMethod('method5', 5, 'method 5', true);
        $method5->setTypes([
            $this->getShippingMethodType('type1', 1, 'type 1'),
            $this->getShippingMethodType('type2', 2, 'type 2')
        ]);
        $method5->getType('type2')->setOptionsConfigurationFormType('');

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'method1' => $method1,
                'method2' => $method2,
                'method3' => $method3,
                'method4' => $method4,
                'method5' => $method5
            ]);

        $this->assertEquals(
            [
                'method 2' => 'method2',
                'method 3' => 'method3',
                'method 5' => 'method5'
            ],
            $this->choicesProvider->getMethods()
        );
    }
}
