<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ErrorDataList
{
    /**
     * @var ErrorData[]
     */
    protected $ERROR;

    public function __construct()
    {
    }

    /**
     * @return ErrorData[]
     */
    public function getError()
    {
        return $this->ERROR;
    }

    /**
     * @param ErrorData[] $ERROR
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ErrorDataList
     */
    public function setError(array $ERROR = null)
    {
        $this->ERROR = $ERROR;

        return $this;
    }
}
