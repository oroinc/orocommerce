<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Event\AbstractPriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class CombinedPriceListQueueListener
{
    /**
     * @var bool
     */
    protected $hasChanges = false;

    /**
     * @var CombinedPriceListQueueConsumer
     */
    protected $queueConsumer;

    /**
     * @var ConfigManager
     */
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
        $key = OroB2BPricingExtension::ALIAS
            . ConfigManager::SECTION_MODEL_SEPARATOR
            . Configuration::PRICE_LISTS_UPDATE_MODE;
        $isRealTimeMode = $this->configManager->get($key) === CombinedPriceListQueueConsumer::MODE_REAL_TIME;
        if ($isRealTimeMode) {
            $this->queueConsumer->process();
        }
    }

    /**
     * @param AbstractPriceListQueueChangeEvent $event
     */
    public function onQueueChanged(AbstractPriceListQueueChangeEvent $event)
    {
        $this->hasChanges = true;
    }
}
