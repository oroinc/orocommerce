<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ResponseApplyTransaction extends GenericResponse
{
    /**
     * @var string
     */
    protected $ACCOUNTING_DT;

    /**
     * @var float
     */
    protected $GUAR_AMT;

    /**
     * @var int
     */
    protected $REF_NO;

    /**
     * @var float
     */
    protected $TRS_AMT;

    /**
     * @var string
     */
    protected $TRS_CURRENCY;

    /**
     * @var string
     */
    protected $TRS_TYPE;

    /**
     * @param \DateTime $ACCOUNTING_DT
     * @param float     $GUAR_AMT
     * @param int       $REF_NO
     * @param float     $TRS_AMT
     * @param string    $TRS_CURRENCY
     * @param string    $TRS_TYPE
     */
    public function __construct(
        \DateTime $ACCOUNTING_DT = null,
        $GUAR_AMT = null,
        $REF_NO = null,
        $TRS_AMT = null,
        $TRS_CURRENCY = null,
        $TRS_TYPE = null
    ) {
        $this->ACCOUNTING_DT = $ACCOUNTING_DT ? $ACCOUNTING_DT->format(\DateTime::ATOM) : null;
        $this->GUAR_AMT = $GUAR_AMT;
        $this->REF_NO = $REF_NO;
        $this->TRS_AMT = $TRS_AMT;
        $this->TRS_CURRENCY = $TRS_CURRENCY;
        $this->TRS_TYPE = $TRS_TYPE;
    }

    /**
     * @return \DateTime|bool
     */
    public function getAccountingDt()
    {
        if ($this->ACCOUNTING_DT === null) {
            return null;
        } else {
            try {
                return new \DateTime($this->ACCOUNTING_DT);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param \DateTime $ACCOUNTING_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
     */
    public function setAccountingDt(\DateTime $ACCOUNTING_DT)
    {
        $this->ACCOUNTING_DT = $ACCOUNTING_DT->format(\DateTime::ATOM);

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
     */
    public function setRefNo($REF_NO)
    {
        $this->REF_NO = $REF_NO;

        return $this;
    }

    /**
     * @return float
     */
    public function getTrsAmt()
    {
        return $this->TRS_AMT;
    }

    /**
     * @param float $TRS_AMT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
     */
    public function setTrsAmt($TRS_AMT)
    {
        $this->TRS_AMT = $TRS_AMT;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsCurrency()
    {
        return $this->TRS_CURRENCY;
    }

    /**
     * @param string $TRS_CURRENCY
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
     */
    public function setTrsCurrency($TRS_CURRENCY)
    {
        $this->TRS_CURRENCY = $TRS_CURRENCY;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsType()
    {
        return $this->TRS_TYPE;
    }

    /**
     * @param string $TRS_TYPE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ResponseApplyTransaction
     */
    public function setTrsType($TRS_TYPE)
    {
        $this->TRS_TYPE = $TRS_TYPE;

        return $this;
    }
}
