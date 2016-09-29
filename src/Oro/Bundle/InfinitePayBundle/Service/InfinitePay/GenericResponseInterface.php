<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

interface GenericResponseInterface
{
    /**
     * @return ErrorDataList
     */
    public function getErrorData();

    /**
     * @param ErrorDataList $ERROR_DATA
     */
    public function setErrorData($ERROR_DATA);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param int $REQUEST_ID
     */
    public function setRequestId($REQUEST_ID);

    /**
     * @return ResponseData
     */
    public function getResponseData();

    /**
     * @param ResponseData $RESPONSE_DATA
     */
    public function setResponseData($RESPONSE_DATA);
}
