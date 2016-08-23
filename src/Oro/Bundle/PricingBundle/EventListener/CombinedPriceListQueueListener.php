<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use Oro\Bundle\PricingBundle\Builder\PriceRuleQueueConsumer;
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
     * @var CombinedPriceListQueueConsumer
     */
    protected $priceRuleQueueConsumer;

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
     * @param CombinedPriceListQueueConsumer|PriceRuleQueueConsumer $priceRuleQueueConsumer
     * @param ConfigManager $configManager
     */
    public function __construct(
        CombinedPriceListQueueConsumer $priceListQueueConsumer,
        CombinedProductPriceQueueConsumer $productPriceQueueConsumer,
        PriceRuleQueueConsumer $priceRuleQueueConsumer,
        ConfigManager $configManager
    ) {
        $this->priceListQueueConsumer = $priceListQueueConsumer;
        $this->productPriceQueueConsumer = $productPriceQueueConsumer;
        $this->priceRuleQueueConsumer = $priceRuleQueueConsumer;
        $this->configManager = $configManager;
    }

    public function onTerminate()
    {
        if ($this->hasRulesChanges) {
            if ($this->isRealTimeMode()) {
                $this->priceRuleQueueConsumer->process();
                $this->priceListQueueConsumer->process();
            }
        }
        if ($this->hasCollectionChanges) {
            if ($this->isRealTimeMode()) {
                $this->priceListQueueConsumer->process();
            }
        }
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

    public function onPriceRuleChanged()
    {
        $this->hasRulesChanges = true;
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
