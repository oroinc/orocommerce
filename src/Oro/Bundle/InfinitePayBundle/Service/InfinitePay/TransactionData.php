<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class TransactionData
{
    /**
     * @var string
     */
    protected $ACCOUNTING_DT;

    /**
     * @var string
     */
    protected $COMMENT;

    /**
     * @var string
     */
    protected $DOC_ID;

    /**
     * @var string
     */
    protected $TRS_AMT;

    /**
     * @var string
     */
    protected $TRS_CURRENCY;

    /**
     * @var string
     */
    protected $TRS_REASON;

    /**
     * @var string
     */
    protected $TRS_TYPE;

    /**
     * @var string
     */
    protected $VALUE_DT;

    /**
     * @param string $ACCOUNTING_DT
     * @param string $COMMENT
     * @param string $DOC_ID
     * @param string $TRS_AMT
     * @param string $TRS_CURRENCY
     * @param string $TRS_REASON
     * @param string $TRS_TYPE
     * @param string $VALUE_DT
     */
    public function __construct(
        $ACCOUNTING_DT = null,
        $COMMENT = null,
        $DOC_ID = null,
        $TRS_AMT = null,
        $TRS_CURRENCY = null,
        $TRS_REASON = null,
        $TRS_TYPE = null,
        $VALUE_DT = null
    ) {
        $this->ACCOUNTING_DT = $ACCOUNTING_DT;
        $this->COMMENT = $COMMENT;
        $this->DOC_ID = $DOC_ID;
        $this->TRS_AMT = $TRS_AMT;
        $this->TRS_CURRENCY = $TRS_CURRENCY;
        $this->TRS_REASON = $TRS_REASON;
        $this->TRS_TYPE = $TRS_TYPE;
        $this->VALUE_DT = $VALUE_DT;
    }

    /**
     * @return string
     */
    public function getAccountingDt()
    {
        return $this->ACCOUNTING_DT;
    }

    /**
     * @param string $ACCOUNTING_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setAccountingDt($ACCOUNTING_DT)
    {
        $this->ACCOUNTING_DT = $ACCOUNTING_DT;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->COMMENT;
    }

    /**
     * @param string $COMMENT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setComment($COMMENT)
    {
        $this->COMMENT = $COMMENT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocId()
    {
        return $this->DOC_ID;
    }

    /**
     * @param string $DOC_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setDocId($DOC_ID)
    {
        $this->DOC_ID = $DOC_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsAmt()
    {
        return $this->TRS_AMT;
    }

    /**
     * @param string $TRS_AMT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setTrsCurrency($TRS_CURRENCY)
    {
        $this->TRS_CURRENCY = $TRS_CURRENCY;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsReason()
    {
        return $this->TRS_REASON;
    }

    /**
     * @param string $TRS_REASON
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setTrsReason($TRS_REASON)
    {
        $this->TRS_REASON = $TRS_REASON;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setTrsType($TRS_TYPE)
    {
        $this->TRS_TYPE = $TRS_TYPE;

        return $this;
    }

    /**
     * @return string
     */
    public function getValueDt()
    {
        return $this->VALUE_DT;
    }

    /**
     * @param string $VALUE_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\TransactionData
     */
    public function setValueDt($VALUE_DT)
    {
        $this->VALUE_DT = $VALUE_DT;

        return $this;
    }
}
