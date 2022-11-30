<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Component\Testing\ReflectionUtil;

class ChannelShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'custom_type';

    /** @var ShippingMethodInterface */
    private $enabledMethod;

    /** @var ShippingMethodInterface */
    private $disabledMethod;

    /** @var ChannelShippingMethodProvider */
    private $provider;

    protected function setUp(): void
    {
        $loadedChannel = $this->getChannel('ch_enabled');
        $fetchedChannel = $this->getChannel('ch_disabled');

        $this->enabledMethod = $this->getShippingMethod('ups_10');
        $this->disabledMethod = $this->getShippingMethod('ups_20');

        $methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $methodFactory->expects(self::any())
            ->method('create')
            ->willReturnMap([
                [$loadedChannel, $this->enabledMethod],
                [$fetchedChannel, $this->disabledMethod],
            ]);

        $repository = $this->createMock(ChannelRepository::class);
        $repository->expects(self::any())
            ->method('findByTypeAndExclude')
            ->willReturnCallback(function () use ($fetchedChannel) {
                $this->provider->postLoad($fetchedChannel);

                return [$fetchedChannel];
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->with(Channel::class)
            ->willReturn($repository);

        $this->provider = new ChannelShippingMethodProvider(self::TYPE, $doctrineHelper, $methodFactory);
        $this->provider->postLoad($loadedChannel);
    }

    private function getChannel(string $name): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, 20);
        $channel->setName($name);
        $channel->setType(self::TYPE);

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

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        self::assertCount(2, $methods);
        $actualMethod = reset($methods);
        self::assertSame($this->enabledMethod, $actualMethod);
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod($this->enabledMethod->getIdentifier());
        self::assertInstanceOf(ShippingMethodInterface::class, $method);
    }

    public function testHasShippingMethod()
    {
        self::assertTrue($this->provider->hasShippingMethod($this->enabledMethod->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        self::assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
