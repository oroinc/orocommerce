<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\FrontendBundle\Model\LocaleSettings as FrontendLocaleSettings;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContextStack;

/**
 * Provides locale settings for store front with selected currency in currency switcher.
 */
class LocaleSettings extends FrontendLocaleSettings
{
    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    public function __construct(
        BaseLocaleSettings $inner,
        FrontendHelper $frontendHelper,
        LocalizationProviderInterface $localizationProvider,
        UserCurrencyManager $currencyManager,
        LayoutContextStack $layoutContextStack,
        ThemeManager $themeManager
    ) {
        parent::__construct($inner, $frontendHelper, $localizationProvider, $layoutContextStack, $themeManager);

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
