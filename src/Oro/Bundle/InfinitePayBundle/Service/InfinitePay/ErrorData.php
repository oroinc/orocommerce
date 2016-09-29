<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ErrorData
{
    /**
     * @var string
     */
    protected $ERROR_CD;

    /**
     * @var string
     */
    protected $ERROR_MSG;

    /**
     * @param string $ERROR_CD
     * @param string $ERROR_MSG
     */
    public function __construct($ERROR_CD = null, $ERROR_MSG = null)
    {
        $this->ERROR_CD = $ERROR_CD;
        $this->ERROR_MSG = $ERROR_MSG;
    }

    /**
     * @return string
     */
    public function geErrorCd()
    {
        return $this->ERROR_CD;
    }

    /**
     * @param string $ERROR_CD
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ErrorData
     */
    public function setErrorCd($ERROR_CD)
    {
        $this->ERROR_CD = $ERROR_CD;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->ERROR_MSG;
    }

    /**
     * @param string $ERROR_MSG
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ErrorData
     */
    public function setErrorMsg($ERROR_MSG)
    {
        $this->ERROR_MSG = $ERROR_MSG;

        return $this;
    }
}
