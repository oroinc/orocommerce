<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

abstract class AbstractRelatedItemConfigProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    abstract public function isEnabled();

    /**
     * @return int
     */
    abstract public function getLimit();

    /**
     * @return bool
     */
    abstract public function isBidirectional();

    /**
     * @return int
     */
    abstract public function getMinimumItems();

    /**
     * @return int
     */
    abstract public function getMaximumItems();

    /**
     * @return bool
     */
    abstract public function isSliderEnabledOnMobile();

    /**
     * @return bool
     */
    abstract public function isAddButtonVisible();
}
