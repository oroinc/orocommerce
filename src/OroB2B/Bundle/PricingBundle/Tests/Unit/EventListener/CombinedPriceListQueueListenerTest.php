<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;

use OroB2B\Bundle\PricingBundle\Builder\PriceRuleQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\EventListener\CombinedPriceListQueueListener;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;

class CombinedPriceListQueueListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListQueueConsumer
     */
    protected $priceListQueueConsumer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedProductPriceQueueConsumer
     */
    protected $productPriceQueueConsumer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceRuleQueueConsumer
     */
    protected $priceRuleQueueConsumer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var CombinedPriceListQueueListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->priceListQueueConsumer = $this
            ->getMockBuilder(CombinedPriceListQueueConsumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productPriceQueueConsumer = $this
            ->getMockBuilder(CombinedProductPriceQueueConsumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceRuleQueueConsumer = $this
            ->getMockBuilder(PriceRuleQueueConsumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CombinedPriceListQueueListener(
            $this->priceListQueueConsumer,
            $this->productPriceQueueConsumer,
            $this->priceRuleQueueConsumer,
            $this->configManager
        );
    }

    /**
     * @dataProvider onTerminateDataProvider
     * @param bool $changes
     * @param array $expects
     * @param string $queueConsumerMode
     */
    public function testOnTerminate($changes, array $expects, $queueConsumerMode)
    {
        $this->priceListQueueConsumer->expects($this->exactly($expects['process']))->method('process');
        $this->productPriceQueueConsumer->expects($this->exactly($expects['process']))->method('process');
        $this->configManager->expects($this->exactly($expects['config.get']))
            ->method('get')
            ->with(OroB2BPricingExtension::ALIAS . '.price_lists_update_mode')
            ->willReturn($queueConsumerMode);

        if ($changes) {
            $this->listener->onQueueChanged();
            $this->listener->onProductPriceChanged();
        }
        $this->listener->onTerminate();
    }

    public function testPriceRuleChange()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(OroB2BPricingExtension::ALIAS
                . ConfigManager::SECTION_MODEL_SEPARATOR
                . Configuration::PRICE_LISTS_UPDATE_MODE)
            ->willReturn(CombinedPriceListQueueConsumer::MODE_REAL_TIME);

        $this->listener->onPriceRuleChanged();
        $this->priceRuleQueueConsumer->expects($this->once())->method('process');
        $this->priceListQueueConsumer->expects($this->once())->method('process');
        $this->listener->onTerminate();
    }

    /**
     * @return array
     */
    public function onTerminateDataProvider()
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
