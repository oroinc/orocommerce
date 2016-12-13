<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class InvalidateCacheAtHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShippingPriceCache
     */
    protected $shippingPriceCache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UPSShippingPriceCache
     */
    protected $upsPriceCache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
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
     * @var \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    protected $channel;

    /**
     * @var \DateTime
     */
    protected $datetime;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DeferredScheduler
     */
    protected $deferredScheduler;

    protected function setUp()
    {
        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        /** @var \PHPUnit_Framework_MockObject_MockObject|Registry $managerRegistry */
        $this->managerRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $this->upsPriceCache = $this->getMockBuilder(UPSShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll'])->getMockForAbstractClass();
        
        $this->shippingPriceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteAllPrices'])->getMockForAbstractClass();

        $this->deferredScheduler = $this->getMockBuilder(DeferredScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new InvalidateCacheAtHandler(
            $this->managerRegistry,
            $this->upsPriceCache,
            $this->shippingPriceCache,
            $this->deferredScheduler
        );
    }

    /**
     * @param \DateTime|null $oldDateTime
     * @param \DateTime $newDateTime
     * @param string|null $removeCronString
     * @param string $addCronString
     * @param int $removeQuantity
     * @dataProvider invalidateAtDataProvider
     */
    public function testProcessInvalidateNotNow(
        \DateTime $oldDateTime = null,
        \DateTime $newDateTime,
        $removeCronString,
        $addCronString,
        $removeQuantity
    ) {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock(FormInterface::class);
        $transport = $this->getEntity(
            UPSTransport::class,
            [
                'id' => 1,
                'invalidateCacheAt' => $oldDateTime
            ]
        );

        $this->deferredScheduler->expects(static::exactly($removeQuantity))
            ->method('removeSchedule')
            ->with(
                InvalidateCacheAtHandler::COMMAND,
                [sprintf('--id=%d', $transport->getId())],
                $removeCronString
            );

        $this->deferredScheduler->expects(static::once())
            ->method('addSchedule')
            ->with(
                InvalidateCacheAtHandler::COMMAND,
                [sprintf('--id=%d', $transport->getId())],
                $addCronString
            );

        $this->channel = $this->getMock(Channel::class);
        $this->channel->expects(static::once())
            ->method('getTransport')
            ->willReturn($transport);

        $invalidateNow = $this->getMock(FormInterface::class);
        $invalidateNow->expects(static::once())
            ->method('getData')
            ->willReturn(null);

        $datetime = $newDateTime;
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
    
    public function invalidateAtDataProvider()
    {
        return[
            'withoutOldValue' => [
                'oldDateTime' => null,
                'newDateTime' => new \DateTime('2017-08-21 15:00:00 UTC'),
                'removeCronString' => null,
                'addCronString' => '0 15 21 8 *',
                'removeQuantity' => 0
            ],
            'withOldValue' => [
                'oldDateTime' => new \DateTime('2015-05-15 15:00:00 UTC'),
                'newDateTime' => new \DateTime('2017-08-21 15:00:00 UTC'),
                'removeCronString' => '0 15 15 5 *',
                'addCronString' => '0 15 21 8 *',
                'removeQuantity' => 1
            ]
        ];
    }

    public function testProcessInvalidateNow()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock(FormInterface::class);

        $transport = $this->getEntity(UPSTransport::class, ['id' => 1]);

        $this->channel = $this->getMock(Channel::class);
        $this->channel->expects(static::once())
            ->method('getTransport')
            ->willReturn($transport);

        $invalidateNow = $this->getMock(FormInterface::class);
        $invalidateNow->expects(static::once())
            ->method('getData')
            ->willReturn('1');

        $form->expects(static::once())
            ->method('get')
            ->with('invalidateNow')
            ->willReturn($invalidateNow);

        $this->upsPriceCache->expects(static::once())->method('deleteAll')->with(1);
        $this->shippingPriceCache->expects(static::once())->method('deleteAllPrices');
        $this->handler->process($this->channel, $form);
    }
}
