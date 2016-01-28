<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class CombinedPriceListQueueListener
{
    /** @var bool */
    protected $hasChanges = false;

    /** @var CombinedPriceListQueueConsumer */
    protected $queueConsumer;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param CombinedPriceListQueueConsumer $consumer
     * @param ConfigManager $configManager
     */
    public function __construct(CombinedPriceListQueueConsumer $consumer, ConfigManager $configManager)
    {
        $this->queueConsumer = $consumer;
        $this->configManager = $configManager;
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onTerminate(PostResponseEvent $event)
    {
        if (!$this->hasChanges) {
            return;
        }
        $key = OroB2BPricingExtension::ALIAS . '.price_lists_update_mode';
        $isRealTimeMode = $this->configManager->get($key) == CombinedPriceListQueueConsumer::MODE_REAL_TIME;
        if ($isRealTimeMode) {
            $this->queueConsumer->process();
        }
    }

    /**
     * @param PriceListCollectionChange $event
     */
    public function onQueueChanged(PriceListCollectionChange $event)
    {
        $this->hasChanges = true;
    }
}
