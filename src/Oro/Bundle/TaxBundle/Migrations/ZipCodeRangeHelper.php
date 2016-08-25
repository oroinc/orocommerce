<?php

namespace Oro\Bundle\TaxBundle\Migrations;

class ZipCodeRangeHelper
{
    /**
     * @param array $data
     * @param array $zipCodes
     * @param int $jurisdictionId
     * @param string $time
     */
    public function extractZipCodeRanges(array &$data, array $zipCodes, $jurisdictionId, $time)
    {
        sort($zipCodes);

        $zipRangeStart = null;
        $zipRangeEnd = null;

        foreach ($zipCodes as $index => $zipCode) {
            $range = $this->isRange($zipCodes, $index);
            if ($this->isSingle($range, $zipRangeStart, $zipRangeEnd)) {
                $data[] = [$jurisdictionId, $zipCode, null, null, $time, $time];
                continue;
            }

            if ($this->isRangeLast($range, $zipRangeStart, $zipRangeEnd)) {
                $zipRangeEnd = $zipCode;
            }

            if ($this->isRangeFirst($range, $zipRangeStart, $zipRangeEnd)) {
                $zipRangeStart = $zipCode;
            }

            if ($this->isRangeFinished($range, $zipRangeStart, $zipRangeEnd)) {
                $data[] = [$jurisdictionId, null, $zipRangeStart, $zipRangeEnd, $time, $time];
                $zipRangeStart = null;
                $zipRangeEnd = null;
            }
        }
    }

    /**
     * @param array $items
     * @param int $key
     * @return bool
     */
    protected function isRange(array $items, $key)
    {
        return array_key_exists($key + 1, $items) && $items[$key + 1] == $items[$key] + 1;
    }

    /**
     * @param bool $range
     * @param bool $zipRangeStart
     * @param bool $zipRangeEnd
     * @return bool
     */
    protected function isSingle($range, $zipRangeStart, $zipRangeEnd)
    {
        return !$range && !$zipRangeStart && !$zipRangeEnd;
    }

    /**
     * @param bool $range
     * @param bool $zipRangeStart
     * @param bool $zipRangeEnd
     * @return bool
     */
    protected function isRangeLast($range, $zipRangeStart, $zipRangeEnd)
    {
        return !$range && !$zipRangeEnd;
    }

    /**
     * @param bool $range
     * @param bool $zipRangeStart
     * @param bool $zipRangeEnd
     * @return bool
     */
    protected function isRangeFinished($range, $zipRangeStart, $zipRangeEnd)
    {
        return $zipRangeStart && $zipRangeEnd;
    }

    /**
     * @param bool $range
     * @param bool $zipRangeStart
     * @param bool $zipRangeEnd
     * @return bool
     */
    protected function isRangeFirst($range, $zipRangeStart, $zipRangeEnd)
    {
        return null === $zipRangeStart;
    }
}
