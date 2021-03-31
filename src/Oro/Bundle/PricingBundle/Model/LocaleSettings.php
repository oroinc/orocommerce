<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\FrontendBundle\Model\LocaleSettings as FrontendLocaleSettings;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

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
     * @param UserLocalizationManagerInterface $localizationManager
     * @param UserCurrencyManager $currencyManager
     * @param LayoutContextHolder $layoutContextHolder
     * @param ThemeManager $themeManager
     */
    public function __construct(
        BaseLocaleSettings $inner,
        FrontendHelper $frontendHelper,
        UserLocalizationManagerInterface $localizationManager,
        UserCurrencyManager $currencyManager,
        LayoutContextHolder $layoutContextHolder,
        ThemeManager $themeManager
    ) {
        parent::__construct($inner, $frontendHelper, $localizationManager, $layoutContextHolder, $themeManager);

        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        if (null === $this->currency) {
            if (!$this->frontendHelper->isFrontendRequest()) {
                $this->currency = $this->inner->getCurrency();
            } else {
                $this->currency = $this->currencyManager->getUserCurrency() ?: $this->inner->getCurrency();
            }
        }

        return $this->currency;
    }
}
