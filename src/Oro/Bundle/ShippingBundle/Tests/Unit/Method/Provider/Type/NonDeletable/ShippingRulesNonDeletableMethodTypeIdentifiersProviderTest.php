<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Type\NonDeletable;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable\ShippingRulesNonDeletableMethodTypeIdentifiersProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingRulesNonDeletableMethodTypeIdentifiersProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodTypeConfigRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $methodTypeConfigRepository;

    /** @var ShippingRulesNonDeletableMethodTypeIdentifiersProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->methodTypeConfigRepository = $this->createMock(ShippingMethodTypeConfigRepository::class);

        $this->provider = new ShippingRulesNonDeletableMethodTypeIdentifiersProvider($this->methodTypeConfigRepository);
    }

    public function testGetMethodTypeIdentifiers()
    {
        $typeId1 = 'type_1';
        $typeId2 = 'type_2';
        $disabledTypeId = 'disabled_type';

        $type1 = $this->createMock(ShippingMethodTypeInterface::class);
        $type1->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($typeId1);

        $type2 = $this->createMock(ShippingMethodTypeInterface::class);
        $type2->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($typeId2);

        $methodId = 'method_id';
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($methodId);

        $shippingMethod->expects(self::once())
            ->method('getTypes')
            ->willReturn([$type1, $type2]);

        $methodTypeConfig1 = $this->createMock(ShippingMethodTypeConfig::class);
        $methodTypeConfig1->expects(self::once())
            ->method('getType')
            ->willReturn($typeId1);

        $methodTypeConfig2 = $this->createMock(ShippingMethodTypeConfig::class);
        $methodTypeConfig2->expects(self::once())
            ->method('getType')
            ->willReturn($disabledTypeId);

        $this->methodTypeConfigRepository->expects(self::once())
            ->method('findEnabledByMethodIdentifier')
            ->with($methodId)
            ->willReturn([$methodTypeConfig1, $methodTypeConfig2]);

        $actualNonDeletableTypeIds = $this->provider->getMethodTypeIdentifiers($shippingMethod);

        self::assertCount(1, $actualNonDeletableTypeIds);
        self::assertContains($disabledTypeId, $actualNonDeletableTypeIds);
    }
}
