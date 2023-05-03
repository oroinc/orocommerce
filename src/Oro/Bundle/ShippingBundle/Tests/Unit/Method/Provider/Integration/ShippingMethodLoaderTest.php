<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodLoader;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Component\Testing\ReflectionUtil;

class ShippingMethodLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChannelLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $channelLoader;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ShippingMethodLoader */
    private $shippingMethodLoader;

    protected function setUp(): void
    {
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $this->channelLoader = $this->createMock(ChannelLoaderInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->shippingMethodLoader = new ShippingMethodLoader(
            $this->channelLoader,
            $this->memoryCacheProvider,
            $this->organizationProvider,
            $this->tokenAccessor
        );
    }

    private function getChannel(int $id, string $name, string $type): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);
        $channel->setName($name);
        $channel->setType($type);

        return $channel;
    }

    private function getShippingMethod(string $identifier): ShippingMethodInterface
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $shippingMethod;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testLoadShippingMethods(): void
    {
        $channelType = 'test_channel_type';
        $organization = $this->getOrganization(1);

        $channel1 = $this->getChannel(1, 'channel1', $channelType);
        $channel2 = $this->getChannel(2, 'channel2', $channelType);

        $shippingMethod1 = $this->getShippingMethod('ups_10');
        $shippingMethod2 = $this->getShippingMethod('ups_20');

        $this->organizationProvider->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn($organization->getId());
        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects(self::never())
            ->method('getOrganizationId');

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with($channelType, self::isTrue(), self::identicalTo($organization))
            ->willReturn([$channel1, $channel2]);

        $shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $shippingMethodFactory->expects(self::exactly(2))
            ->method('create')
            ->willReturnMap([
                [$channel1, $shippingMethod1],
                [$channel2, $shippingMethod2],
            ]);

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->with('shipping_methods_channel_test_channel_type_1')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals(
            [
                $shippingMethod1->getIdentifier() => $shippingMethod1,
                $shippingMethod2->getIdentifier() => $shippingMethod2
            ],
            $this->shippingMethodLoader->loadShippingMethods($channelType, $shippingMethodFactory)
        );
    }

    public function testLoadShippingMethodsWhenShippingMethodsCached(): void
    {
        $cachedShippingMethods = [
            'ups_10' => $this->getShippingMethod('ups_10'),
            'ups_20' => $this->getShippingMethod('ups_20')
        ];

        $this->organizationProvider->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(null);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->channelLoader->expects(self::never())
            ->method('loadChannels');

        $shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $shippingMethodFactory->expects(self::never())
            ->method('create');

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->with('shipping_methods_channel_test_channel_type_1')
            ->willReturnCallback(function () use ($cachedShippingMethods) {
                return $cachedShippingMethods;
            });

        self::assertEquals(
            $cachedShippingMethods,
            $this->shippingMethodLoader->loadShippingMethods('test_channel_type', $shippingMethodFactory)
        );
    }

    public function testLoadShippingMethodsWhenNoOrganization(): void
    {
        $channelType = 'test_channel_type';
        $channel = $this->getChannel(1, 'channel1', $channelType);
        $shippingMethod = $this->getShippingMethod('ups_10');

        $this->organizationProvider->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(null);
        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganizationId')
            ->willReturn(null);

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with($channelType, self::isTrue(), self::isNull())
            ->willReturn([$channel]);

        $shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $shippingMethodFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($channel))
            ->willReturn($shippingMethod);

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->with('shipping_methods_channel_test_channel_type_')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals(
            [$shippingMethod->getIdentifier() => $shippingMethod],
            $this->shippingMethodLoader->loadShippingMethods($channelType, $shippingMethodFactory)
        );
    }
}
