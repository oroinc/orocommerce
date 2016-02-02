<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Event\ProductPriceChange;
use OroB2B\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;

class CombinedPriceListQueueListener
{
    /**
     * @var bool
     */
    protected $hasCollectionChanges = false;

    /**
     * @var bool
     */
    protected $hasProductPriceChanges = false;

    /**
     * @var CombinedPriceListQueueConsumer
     */
    protected $priceListQueueConsumer;

    /**
     * @var CombinedProductPriceQueueConsumer
     */
    protected $productPriceQueueConsumer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool|null
     */
    protected $isRealTimeMode;

    /**
     * @param CombinedPriceListQueueConsumer $priceListQueueConsumer
     * @param CombinedProductPriceQueueConsumer $productPriceQueueConsumer
     * @param ConfigManager $configManager
     */
    public function __construct(
        CombinedPriceListQueueConsumer $priceListQueueConsumer,
        CombinedProductPriceQueueConsumer $productPriceQueueConsumer,
        ConfigManager $configManager
    ) {
        $this->priceListQueueConsumer = $priceListQueueConsumer;
        $this->productPriceQueueConsumer = $productPriceQueueConsumer;
        $this->configManager = $configManager;
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onTerminate(PostResponseEvent $event)
    {
        if ($this->hasCollectionChanges) {
            if ($this->isRealTimeMode()) {
                $this->priceListQueueConsumer->process();
            }
        }
        if (true || $this->hasProductPriceChanges) {
            if ($this->isRealTimeMode()) {
                $this->productPriceQueueConsumer->process();
            }
        }
    }

    /**
     * @param PriceListCollectionChange $event
     */
    public function onQueueChanged(PriceListCollectionChange $event)
    {
        $this->hasCollectionChanges = true;
    }

    /**
     * @param ProductPriceChange $event
     */
    public function onProductPriceChanged(ProductPriceChange $event)
    {
        $this->hasProductPriceChanges = true;
    }

    /**
     * @return bool
     */
    protected function isRealTimeMode()
    {
        if ($this->isRealTimeMode === null) {
            $key = OroB2BPricingExtension::ALIAS
                . ConfigManager::SECTION_MODEL_SEPARATOR
                . Configuration::PRICE_LISTS_UPDATE_MODE;
            $this->isRealTimeMode = $this->configManager->get($key) === CombinedPriceListQueueConsumer::MODE_REAL_TIME;
        }

        return $this->isRealTimeMode;
    }
}
