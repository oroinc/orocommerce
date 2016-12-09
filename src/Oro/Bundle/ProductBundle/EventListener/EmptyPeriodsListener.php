<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\Event\EmptyPeriodsConfigurationEvent;

//TODO: Make listener more generic, column names should be resolved through/from filters
class EmptyPeriodsListener
{
    /** @var array */
    protected $parameters;

    /** @var array */
    protected $datesInResult;

    /**
     * @param EmptyPeriodsConfigurationEvent $event
     */
    public function onConfiguration(EmptyPeriodsConfigurationEvent $event)
    {
        $this->parameters = $event->getParameters();
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if (!is_array($this->parameters)) {
            return;
        }
        $records = $event->getRecords();
        $datagridFilters = $event->getDatagrid()->getParameters()->get('_filter');
        $datagridSorters = $event->getDatagrid()->getParameters()->get('_sort_by');

        //TODO get default constant matching the $groupBy below
        $startDate = new \DateTime('-10 years');
        $endDate = new \DateTime('-10 days');

        if (array_key_exists('createdAt', $datagridFilters)) {
            $timeFilter = $datagridFilters['createdAt'];
            $startDate = new \DateTime($timeFilter['value']['start']);
            $endDate = new \DateTime($timeFilter['value']['end']);
        }

        //TODO is pagination possible ?
        $maxResults = 25;
        $firstResult = 1;

        //TODO get columns from event parameter list
        $groupBy = $datagridFilters['grouping']['value'];
        $order = array_key_exists('createdAt', $datagridFilters) ? $datagridSorters['createdAt'] : 'DESC';
        $format = 'Y';
        $groupColumn = 'dateGrouping';

        foreach ($this->getRequiredDates($startDate, $endDate, $groupBy, $firstResult, $maxResults) as $date) {
            /** \DateTime $date */
            if (!$this->isDayPresent($date, $records, $groupColumn, $format)) {
                $records[] = new ResultRecord(['dateGrouping' => $date->format($format)]);
            }
        }

        usort($records, function (ResultRecord $firstRecord, ResultRecord $secondRecord) use ($groupColumn, $order) {
            $condition = (
                new \DateTime($firstRecord->getValue($groupColumn)) >
                new \DateTime($secondRecord->getValue($groupColumn))
            );
            $order = $order == 'ASC' ? 1 : -1;
            return $condition ? $order : -$order;
        });

        $event->setRecords($records);
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
        $endDate = clone $endDate;
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
     * @param $key
     * @param $format
     * @return bool
     */
    private function isDayPresent(\DateTime $date, array $records, $key, $format)
    {
        if (empty($this->datesInResult)) {
            $this->datesInResult = [];
            foreach ($records as $record) {
                /** @var ResultRecord $record */

                $this->datesInResult[] = $record->getValue($key);
            }
        }

        return in_array($date->format($format), $this->datesInResult);
    }
}
