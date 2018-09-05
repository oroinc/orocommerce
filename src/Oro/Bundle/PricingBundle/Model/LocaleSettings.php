<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\FrontendBundle\Model\LocaleSettings as FrontendLocaleSettings;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * Provides locale settings for store front with selected currency in currency switcher.
 */
class LocaleSettings extends FrontendLocaleSettings
{
    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param BaseLocaleSettings $inner
     * @param FrontendHelper $frontendHelper
     * @param UserLocalizationManager $localizationManager
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        BaseLocaleSettings $inner,
        FrontendHelper $frontendHelper,
        UserLocalizationManager $localizationManager,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($inner, $frontendHelper, $localizationManager);

        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
            return $this->inner->getCurrency();
        }

        $currency = $this->currencyManager->getUserCurrency();

        return $currency ?: $this->inner->getCurrency();
    }
}
