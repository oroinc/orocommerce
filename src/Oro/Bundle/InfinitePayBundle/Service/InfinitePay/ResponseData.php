<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ResponseData
{
    /**
     * @var string
     */
    protected $ADD_INFO;

    /**
     * @var string
     */
    protected $ORDER_ID;

    /**
     * @var int
     */
    protected $DB_ID;

    /**
     * @var int
     */
    protected $GUAR_AMT;

    /**
     * @var int
     */
    protected $REF_NO;

    /**
     * @var string
     */
    protected $STATUS;

    /**
     * @param string $ADD_INFO
     * @param string $ORDER_ID
     * @param int    $DB_ID
     * @param int    $GUAR_AMT
     * @param int    $REF_NO
     * @param string $STATUS
     */
    public function __construct(
        $ADD_INFO = null,
        $ORDER_ID = null,
        $DB_ID = null,
        $GUAR_AMT = null,
        $REF_NO = null,
        $STATUS = null
    ) {
        $this->ADD_INFO = $ADD_INFO;
        $this->ORDER_ID = $ORDER_ID;
        $this->DB_ID = $DB_ID;
        $this->GUAR_AMT = $GUAR_AMT;
        $this->REF_NO = $REF_NO;
        $this->STATUS = $STATUS;
    }

    /**
     * @return string
     */
    public function getAddInfo()
    {
        return $this->ADD_INFO;
    }

    /**
     * @param string $ADD_INFO
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setAddInfo($ADD_INFO)
    {
        $this->ADD_INFO = $ADD_INFO;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->ORDER_ID;
    }

    /**
     * @param string $ORDER_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setOrderId($ORDER_ID)
    {
        $this->ORDER_ID = $ORDER_ID;

        return $this;
    }

    /**
     * @return int
     */
    public function getDbId()
    {
        return $this->DB_ID;
    }

    /**
     * @param int $DB_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setDbId($DB_ID)
    {
        $this->DB_ID = $DB_ID;

        return $this;
    }

    /**
     * @return int
     */
    public function getGuarAmt()
    {
        return $this->GUAR_AMT;
    }

    /**
     * @param int $GUAR_AMT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setGuarAmt($GUAR_AMT)
    {
        $this->GUAR_AMT = $GUAR_AMT;

        return $this;
    }

    /**
     * @return int
     */
    public function getRefNo()
    {
        return $this->REF_NO;
    }

    /**
     * @param int $REF_NO
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setRefNo($REF_NO)
    {
        $this->REF_NO = $REF_NO;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->STATUS;
    }

    /**
     * @param string $STATUS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseData
     */
    public function setStatus($STATUS)
    {
        $this->STATUS = $STATUS;

        return $this;
    }
}
