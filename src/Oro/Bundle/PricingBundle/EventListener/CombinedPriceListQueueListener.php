<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Builder\CombinedProductPriceQueueConsumer;

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
     * @var bool
     */
    protected $hasRulesChanges = false;

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
     * @param CombinedProductPriceQueueConsumer $productPriceQueueConsumer
     * @param ConfigManager $configManager
     */
    public function __construct(
        CombinedProductPriceQueueConsumer $productPriceQueueConsumer,
        ConfigManager $configManager
    ) {
        $this->productPriceQueueConsumer = $productPriceQueueConsumer;
        $this->configManager = $configManager;
    }

    public function onTerminate()
    {
        if ($this->hasProductPriceChanges) {
            if ($this->isRealTimeMode()) {
                $this->productPriceQueueConsumer->process();
            }
        }
    }

    public function onQueueChanged()
    {
        $this->hasCollectionChanges = true;
    }

    public function onProductPriceChanged()
    {
        $this->hasProductPriceChanges = true;
    }

    /**
     * @return bool
     */
    protected function isRealTimeMode()
    {
        if ($this->isRealTimeMode === null) {
            $key = OroPricingExtension::ALIAS
                . ConfigManager::SECTION_MODEL_SEPARATOR
                . Configuration::PRICE_LISTS_UPDATE_MODE;
            $this->isRealTimeMode = $this->configManager->get($key) === CombinedPriceListQueueConsumer::MODE_REAL_TIME;
        }

        return $this->isRealTimeMode;
    }
}
