<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\FilterBundle\Filter\SkipEmptyPeriodsFilter;

//TODO: Make listener more generic
class BestSellingProductsEmptyPeriodsListener
{
    /**
     * @var SkipEmptyPeriodsFilter
     */
    protected $skipEmptyPeriodsFilter;

    /**
     * @param SkipEmptyPeriodsFilter $skipEmptyPeriodsFilter
     */
    public function __construct(SkipEmptyPeriodsFilter $skipEmptyPeriodsFilter)
    {
        $this->skipEmptyPeriodsFilter = $skipEmptyPeriodsFilter;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if (!$this->skipEmptyPeriodsFilter->isActive()) {
            return;
        }
        $records = $event->getRecords();
        $query = $event->getQuery();

        //TODO: Resolve from $query
        $startDate = new \DateTime();
        $endDate = new \DateTime('+10 days');
        $maxResults = 25;
        $firstResult = 1;
        $groupBy = 'day';
        foreach ($this->getRequiredDates($startDate, $endDate, $groupBy, $firstResult, $maxResults) as $date) {
            /** \DateTime $date */
            if (! $this->isDayPresent($date, $records)) {
                $records[] = new ResultRecord(['dateGrouping' => $date->format('Y-m-d')]);
            }
        }
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param $interval
     * @param $firstResult
     * @param $maxResults
     * @return \DatePeriod
     */
    protected function getRequiredDates(\DateTime $startDate, \DateTime $endDate, $interval, $firstResult, $maxResults)
    {
        $endDate = new \DateTime($endDate);
        $endDate->modify('+1 day');

        switch ($interval) {
            case 'day':
                $interval = 'P1D';
                break;
            case 'month':
                $interval = 'P1M';
                break;
            case 'quarter':
                $interval = 'P3M';
                break;
            default:
                $interval = 'P1Y';
        }
        $dateInterval = new \DateInterval($interval);

        return new \DatePeriod($startDate, $dateInterval, $endDate);
    }

    /**
     * @param \DateTime $date
     * @param array $records
     * @return bool
     */
    private function isDayPresent(\DateTime $date, array $records)
    {
        return false;
    }
}
