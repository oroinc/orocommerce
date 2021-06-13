<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Label\Type;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;
use Oro\Bundle\ShippingBundle\Method\Provider\Label\Type\BasicMethodTypeLabelsProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class BasicMethodTypeLabelsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodProvider;

    /** @var BasicMethodTypeLabelsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->methodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->provider = new BasicMethodTypeLabelsProvider($this->methodProvider);
    }

    public function testGetLabels()
    {
        $methodId = 'method_id';
        $typeId1 = 'type_id_1';
        $typeId2 = 'type_id_2';

        $label1 = 'Label 1';
        $label2 = 'Label 2';

        $type1 = $this->createMock(ShippingMethodTypeInterface::class);
        $type1->expects(self::once())
            ->method('getLabel')
            ->willReturn($label1);

        $type2 = $this->createMock(ShippingMethodTypeInterface::class);
        $type2->expects(self::once())
            ->method('getLabel')
            ->willReturn($label2);

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects(self::exactly(2))
            ->method('getType')
            ->willReturnMap([
                [$typeId1, $type1],
                [$typeId2, $type2]
            ]);

        $this->methodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn($method);

        $this->provider->getLabels($methodId, [$typeId1, $typeId2]);
    }

    public function testGetLabelsNoMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Shipping method with identifier: method_id, does not exist.');

        $methodId = 'method_id';

        $this->methodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn(null);

        $this->provider->getLabels($methodId, []);
    }

    public function testGetLabelsNoType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shipping method with identifier: method_id does not contain type with identifier: type_id.'
        );

        $methodId = 'method_id';
        $typeId = 'type_id';

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects(self::once())
            ->method('getType')
            ->with($typeId)
            ->willReturn(null);

        $this->methodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn($method);

        $this->provider->getLabels($methodId, [$typeId]);
    }
}
