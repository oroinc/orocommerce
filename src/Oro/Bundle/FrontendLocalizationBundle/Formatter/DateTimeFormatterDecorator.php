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
        $dateType = $this->getDateType($dateType);
        $pattern = $this->dateTimeFormatter->getPattern($dateType, $timeType, $locale, $value);
        // For store front replace 2 digit year with 4 digit to correctly support dates before 1970
        if ($dateType === self::DEFAULT_FRONTEND_DATE_TYPE
            && $this->frontendHelper->isFrontendRequest()
            && !str_contains($pattern, 'yyyy')
        ) {
            $pattern = str_replace('yy', 'yyyy', $pattern);
            $this->dateTimeFormatter->updatePattern($dateType, $timeType, $locale, $pattern);
        }

        return $pattern;
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
        if (!$pattern) {
            $pattern = $this->getPattern($dateType, $timeType, $locale);
        }

        return $this->dateTimeFormatter
            ->format($date, $this->getDateType($dateType), $timeType, $locale, $timeZone, $pattern);
    }

    /**
     * {@inheritDoc}
     */
    public function formatDate($date, $dateType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern($dateType, \IntlDateFormatter::NONE, $locale);

        return $this->dateTimeFormatter->formatDate($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatYear($date, $dateType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern($dateType, \IntlDateFormatter::NONE, $locale);

        return $this->dateTimeFormatter->formatYear($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatQuarter($date, $dateType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern($dateType, \IntlDateFormatter::NONE, $locale);

        return $this->dateTimeFormatter->formatQuarter($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatMonth($date, $dateType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern($dateType, \IntlDateFormatter::NONE, $locale);

        return $this->dateTimeFormatter->formatMonth($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatDay($date, $dateType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern($dateType, \IntlDateFormatter::NONE, $locale);

        return $this->dateTimeFormatter->formatDay($date, $this->getDateType($dateType), $locale, $timeZone);
    }

    /**
     * {@inheritDoc}
     */
    public function formatTime($date, $timeType = null, $locale = null, $timeZone = null)
    {
        // Trigger pattern update
        $this->getPattern(\IntlDateFormatter::NONE, $timeType, $locale);

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
