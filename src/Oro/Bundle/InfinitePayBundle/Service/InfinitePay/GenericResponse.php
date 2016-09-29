<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class GenericResponse implements GenericResponseInterface
{
    /**
     * @var ErrorDataList
     */
    protected $ERROR_DATA;

    /**
     * @var int
     */
    protected $REQUEST_ID;

    /**
     * @var ResponseData;
     */
    protected $RESPONSE_DATA;

    /**
     * @return ErrorDataList
     */
    public function getErrorData()
    {
        return $this->ERROR_DATA;
    }

    /**
     * @param ErrorDataList $ERROR_DATA
     *
     * @return GenericResponse
     */
    public function setErrorData($ERROR_DATA)
    {
        $this->ERROR_DATA = $ERROR_DATA;

        return $this;
    }

    /**
     * @return int
     */
    public function getRequestId()
    {
        return $this->REQUEST_ID;
    }

    /**
     * @param int $REQUEST_ID
     *
     * @return GenericResponse
     */
    public function setRequestId($REQUEST_ID)
    {
        $this->REQUEST_ID = $REQUEST_ID;

        return $this;
    }

    /**
     * @return ResponseData
     */
    public function getResponseData()
    {
        return $this->RESPONSE_DATA;
    }

    /**
     * @param ResponseData $RESPONSE_DATA
     *
     * @return GenericResponse
     */
    public function setResponseData($RESPONSE_DATA)
    {
        $this->RESPONSE_DATA = $RESPONSE_DATA;

        return $this;
    }
}
