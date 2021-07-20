<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfiguration;
use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;

/**
 * Provides days period and start date for previously purchased products.
 */
class PreviouslyPurchasedConfigProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LocaleSettings  */
    protected $localeSettings;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    public function __construct(
        ConfigManager $configManager,
        LocaleSettings $localeSettings,
        DateTimeFormatter $dateTimeFormatter
    ) {
        $this->configManager = $configManager;
        $this->localeSettings = $localeSettings;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * Return count of days for previously purchased grid filter
     *
     * @return int
     */
    public function getDaysPeriod()
    {
        return (int) $this->configManager->get(
            OrderConfiguration::getConfigKey(OrderConfiguration::CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD)
        );
    }

    /**
     * @return string
     */
    public function getPreviouslyPurchasedStartDateString()
    {
        $daysPeriod = $this->getDaysPeriod();
        $dateTimeZone = new \DateTimeZone($this->localeSettings->getTimeZone());
        $dateTimeInCurrentLocale = $this->getDateTimeInCurrentLocale($dateTimeZone);
        $dateTimeInCurrentLocale->modify(sprintf('-%d days', $daysPeriod));

        return $this->dateTimeFormatter->format($dateTimeInCurrentLocale);
    }

    /**
     * @return \DateTime
     */
    protected function getDateTimeInCurrentLocale(\DateTimeZone $dateTimeZone)
    {
        return \DateTime::createFromFormat(
            'H:i:s',
            '00:00:00',
            $dateTimeZone
        );
    }
}
