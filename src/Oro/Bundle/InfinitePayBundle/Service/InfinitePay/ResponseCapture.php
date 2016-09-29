<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ResponseCapture extends GenericResponse
{
    /**
     * @var string
     */
    protected $ORDER_ID;

    /**
     * @var float
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
     * @param string $ORDER_ID
     * @param float  $GUAR_AMT
     * @param int    $REF_NO
     * @param string $STATUS
     */
    public function __construct($ORDER_ID = null, $GUAR_AMT = null, $REF_NO = null, $STATUS = null)
    {
        $this->ORDER_ID = $ORDER_ID;
        $this->GUAR_AMT = $GUAR_AMT;
        $this->REF_NO = $REF_NO;
        $this->STATUS = $STATUS;
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseCapture
     */
    public function setOrderId($ORDER_ID)
    {
        $this->ORDER_ID = $ORDER_ID;

        return $this;
    }

    /**
     * @return float
     */
    public function getGuarAmt()
    {
        return $this->GUAR_AMT;
    }

    /**
     * @param float $GUAR_AMT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseCapture
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseCapture
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseCapture
     */
    public function setStatus($STATUS)
    {
        $this->STATUS = $STATUS;

        return $this;
    }
}
