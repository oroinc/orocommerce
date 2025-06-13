<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Converter;

use Oro\Bundle\OrderBundle\ImportExport\Converter\OrderStatusMappingProvider;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderStatusMappingProviderTest extends TestCase
{
    private OrderConfigurationProviderInterface&MockObject $configurationProvider;
    private OrderStatusMappingProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);
        $this->provider = new OrderStatusMappingProvider($this->configurationProvider);
    }

    public function testGetMappingWithoutDisabledExternalStatuses(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(false);

        self::assertEquals([], $this->provider->getMapping([]));
    }

    public function testGetMappingWithDisabledExternalStatuses(): void
    {
        $mapping = [];
        $mapping['order']['fields']['status'] = [
            'target_path' => 'relationships.status.data',
            'ref' => 'order_status'
        ];
        $mapping['order_status'] = [
            'target_type' => 'orderstatuses',
            'entity' => 'Extend\Entity\EV_Order_Status',
            'lookup_field' => 'name',
            'ignore_not_found' => true
        ];

        $this->configurationProvider->expects($this->once())
            ->method('isExternalStatusManagementEnabled')
            ->willReturn(true);

        self::assertEquals($mapping, $this->provider->getMapping([]));
    }
}
