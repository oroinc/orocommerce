<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\EventListener\CombinedPriceListQueueListener;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class CombinedPriceListQueueListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testOnTerminateDataProvider
     * @param bool $changes
     * @param array $expects
     * @param string $queueConsumerMode
     */
    public function testOnTerminate($changes, array $expects, $queueConsumerMode)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListQueueConsumer $consumer */
        $consumer = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer')
            ->disableOriginalConstructor()
            ->getMock();
        $consumer->expects($this->exactly($expects['process']))->method('process');

        /** @var \PHPUnit_Framework_MockObject_MockObject|PostResponseEvent $terminateEvent */
        $terminateEvent = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\PostResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly($expects['config.get']))
            ->method('get')
            ->with(OroB2BPricingExtension::ALIAS . '.price_lists_update_mode')
            ->willReturn($queueConsumerMode);

        $listener = new CombinedPriceListQueueListener($consumer, $configManager);

        if ($changes) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListQueueChangeEvent $changeEvent */
            $changeEvent = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange')
                ->disableOriginalConstructor()
                ->getMock();
            $listener->onQueueChanged($changeEvent);
        }
        $listener->onTerminate($terminateEvent);
    }

    /**
     * @return array
     */
    public function testOnTerminateDataProvider()
    {
        return [
            [
                'changes' => true,
                'expects' => [
                    'process' => 1,
                    'config.get' => 1,
                ],
                'queueConsumerMode' => CombinedPriceListQueueConsumer::MODE_REAL_TIME,
            ],
            [
                'changes' => false,
                'expects' => [
                    'process' => 0,
                    'config.get' => 0,
                ],
                'queueConsumerMode' => CombinedPriceListQueueConsumer::MODE_REAL_TIME,
            ],
            [
                'changes' => true,
                'expects' => [
                    'process' => 0,
                    'config.get' => 1,
                ],
                'queueConsumerMode' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
            ],
            [
                'changes' => false,
                'expects' => [
                    'process' => 0,
                    'config.get' => 0,
                ],
                'queueConsumerMode' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
            ],
        ];
    }
}
