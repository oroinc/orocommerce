<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler;
use Symfony\Component\Form\FormInterface;

class InvalidateCacheAtHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $managerRegistry;

    /**
     * @var InvalidateCacheAtHandler
     */
    protected $handler;

    /**
     * @var UPSTransport
     */
    protected $transport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject| Channel
     */
    protected $channel;

    /**
     * @var \DateTime
     */
    protected $datetime;

    protected function setUp()
    {
        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Registry $managerRegistry */
        $this->managerRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $this->shippingPriceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll'])->getMockForAbstractClass();

        $this->handler = new InvalidateCacheAtHandler(
            $this->managerRegistry,
            $this->shippingPriceCache
        );
    }

    public function testProcessInvalidateNotNow()
    {
        $form = $this->getMock(FormInterface::class);
        $transport = new UPSTransport();

        $this->channel = $this->getMock(Channel::class);
        $this->channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $invalidateNow = $this->getMock(FormInterface::class);
        $invalidateNow->expects(static::once())
            ->method('getData')
            ->willReturn(null);

        $datetime = new \DateTime('2015-08-21 15:00:00 UTC');
        $form->expects(static::at(0))
            ->method('get')
            ->with('invalidateNow')
            ->willReturn($invalidateNow);

        $invalidateCacheAt = $this->getMock(FormInterface::class);
        $invalidateCacheAt->expects(static::once())
            ->method('getData')
            ->willReturn($datetime);

        $form->expects(static::at(1))
            ->method('get')
            ->with('invalidateCacheAt')
            ->willReturn($invalidateCacheAt);

        $this->manager->expects(static::once())
            ->method('flush');

        $this->shippingPriceCache->expects(static::never())->method('deleteAll');
        $this->handler->process($this->channel, $form);
    }

    public function testProcessInvalidateNow()
    {
        $form = $this->getMock(FormInterface::class);

        $this->channel = $this->getMock(Channel::class);
        $this->channel->expects($this->never())
            ->method('getTransport');

        $invalidateNow = $this->getMock(FormInterface::class);
        $invalidateNow->expects(static::once())
            ->method('getData')
            ->willReturn('1');

        $form->expects(static::once())
            ->method('get')
            ->with('invalidateNow')
            ->willReturn($invalidateNow);

        $this->shippingPriceCache->expects(static::once())->method('deleteAll');
        $this->handler->process($this->channel, $form);
    }
}
