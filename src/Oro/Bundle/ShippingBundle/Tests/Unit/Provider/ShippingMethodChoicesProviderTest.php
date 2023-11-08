<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;

class ShippingMethodChoicesProviderTest extends \PHPUnit\Framework\TestCase
{
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private ShippingMethodChoicesProvider $choicesProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->choicesProvider = new ShippingMethodChoicesProvider($this->shippingMethodProvider);
    }

    private function getShippingMethod(
        string $identifier,
        string $name,
        int $sortOrder,
        bool $enabled
    ): ShippingMethodStub {
        $shippingMethod = new ShippingMethodStub();
        $shippingMethod->setIdentifier($identifier);
        $shippingMethod->setName($name);
        $shippingMethod->setSortOrder($sortOrder);
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
        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethods')
            ->willReturn([
                $this->getShippingMethod('flat_rate', 'Flat Rate', 1, true),
                $this->getShippingMethod('disabled', '', 2, false),
                $this->getShippingMethod('ups', 'UPS', 3, true),
                $this->getShippingMethod('ups_2', 'UPS', 4, true)
            ]);

        $this->assertEquals(
            [
                'Flat Rate' => 'flat_rate',
                'UPS'       => 'ups',
                'UPS (2)'   => 'ups_2',
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
        $method1 = $this->getShippingMethod('method1', 'method 1', 1, true);
        $method1->setOptionsConfigurationFormType('');

        $method2 = $this->getShippingMethod('method2', 'method 2', 2, true);

        $method3 = $this->getShippingMethod('method3', 'method 3', 3, true);
        $method3->setTypes([$this->getShippingMethodType('type1', 1, 'type 1')]);

        $method4 = $this->getShippingMethod('method4', 'method 4', 4, true);
        $method4->setTypes([
            $this->getShippingMethodType('type1', 1, 'type 1'),
            $this->getShippingMethodType('type2', 2, 'type 2')
        ]);
        $method4->getType('type1')->setOptionsConfigurationFormType('');
        $method4->getType('type2')->setOptionsConfigurationFormType('');

        $method5 = $this->getShippingMethod('method5', 'method 5', 5, true);
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
