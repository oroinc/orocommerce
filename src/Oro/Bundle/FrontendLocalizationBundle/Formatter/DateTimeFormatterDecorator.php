<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Formatter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;

/**
 * Set short date format on frontend
 */
class DateTimeFormatterDecorator implements DateTimeFormatterInterface
{
    protected const DEFAULT_FRONTEND_DATE_TYPE = \IntlDateFormatter::SHORT;

    /**
     * @var DateTimeFormatterInterface
     */
    private $dateTimeFormatter;

    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(
        DateTimeFormatterInterface $dateTimeFormatter,
        FrontendHelper $frontendHelper
    ) {
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getPattern($dateType, $timeType, $locale = null, $value = null)
    {
        return $this->dateTimeFormatter->getPattern($this->getDateType($dateType), $timeType, $locale, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function format(
        $date,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        $pattern = null
    ) {
        return $this->dateTimeFormatter
            ->format($date, $this->getDateType($dateType), $timeType, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritDoc}
     */
    public function formatDate($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatDate($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatYear($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatYear($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatQuarter($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatQuarter($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatMonth($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatMonth($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatDay($date, $dateType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatDay($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatTime($date, $timeType = null, $locale = null, $timeZone = null)
    {
        return $this->dateTimeFormatter->formatTime($date, $timeType, $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTime($date)
    {
        return $this->dateTimeFormatter->getDateTime($date);
    }

    /**
     * @param int|string|null $dateType
     * @return int|string|null
     */
    protected function getDateType($dateType)
    {
        return $dateType === null && $this->frontendHelper->isFrontendRequest() ?
            static::DEFAULT_FRONTEND_DATE_TYPE :
            $dateType;
    }
}
