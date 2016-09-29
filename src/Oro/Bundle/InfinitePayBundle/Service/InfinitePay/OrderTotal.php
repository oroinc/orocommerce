<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class OrderTotal
{
    /**
     * @var string
     */
    protected $AUTO_ACTIVATE;

    /**
     * @var string
     */
    protected $AUTO_CAPTURE;

    /**
     * @var string
     */
    protected $ORDER_ID;

    /**
     * @var string
     */
    protected $PAY_ID;

    /**
     * @var string
     */
    protected $PAY_TYPE;

    /**
     * @var string
     */
    protected $RABATE_GROSS;

    /**
     * @var string
     */
    protected $RABATE_NET;

    /**
     * @var string
     */
    protected $REF_NO;

    /**
     * @var string
     */
    protected $SHIPPING_NAME;

    /**
     * @var string
     */
    protected $SHIPPING_PRICE_GROSS;

    /**
     * @var string
     */
    protected $SHIPPING_PRICE_NET;

    /**
     * @var string
     */
    protected $TERMS_ACCEPTED;

    /**
     * @var string
     */
    protected $TRS_AMT_GROSS;

    /**
     * @var string
     */
    protected $TRS_AMT_NET;

    /**
     * @var string
     */
    protected $TRS_CURRENCY;

    /**
     * @var string
     */
    protected $TRS_DT;

    /**
     * @var string
     */
    protected $TRS_ID;

    /**
     * @var string
     */
    protected $TRS_VAT_PERC;

    /**
     * @var string
     */
    protected $TOTAL_GROSS_CALC_METHOD;

    /**
     * @param string $AUTO_ACTIVATE
     * @param string $AUTO_CAPTURE
     * @param string $ORDER_ID
     * @param string $PAY_ID
     * @param string $PAY_TYPE
     * @param string $RABATE_GROSS
     * @param string $RABATE_NET
     * @param string $REF_NO
     * @param string $SHIPPING_NAME
     * @param string $SHIPPING_PRICE_GROSS
     * @param string $SHIPPING_PRICE_NET
     * @param string $TERMS_ACCEPTED
     * @param string $TRS_AMT_GROSS
     * @param string $TRS_AMT_NET
     * @param string $TRS_CURRENCY
     * @param string $TRS_DT
     * @param string $TRS_ID
     * @param string $TRS_VAT_PERC
     */
    public function __construct(
        $AUTO_ACTIVATE = null,
        $AUTO_CAPTURE = null,
        $ORDER_ID = null,
        $PAY_ID = null,
        $PAY_TYPE = null,
        $RABATE_GROSS = null,
        $RABATE_NET = null,
        $REF_NO = null,
        $SHIPPING_NAME = null,
        $SHIPPING_PRICE_GROSS = null,
        $SHIPPING_PRICE_NET = null,
        $TERMS_ACCEPTED = null,
        $TRS_AMT_GROSS = null,
        $TRS_AMT_NET = null,
        $TRS_CURRENCY = null,
        $TRS_DT = null,
        $TRS_ID = null,
        $TRS_VAT_PERC = null
    ) {
        $this->AUTO_ACTIVATE = $AUTO_ACTIVATE;
        $this->AUTO_CAPTURE = $AUTO_CAPTURE;
        $this->ORDER_ID = $ORDER_ID;
        $this->PAY_ID = $PAY_ID;
        $this->PAY_TYPE = $PAY_TYPE;
        $this->RABATE_GROSS = $RABATE_GROSS;
        $this->RABATE_NET = $RABATE_NET;
        $this->REF_NO = $REF_NO;
        $this->SHIPPING_NAME = $SHIPPING_NAME;
        $this->SHIPPING_PRICE_GROSS = $SHIPPING_PRICE_GROSS;
        $this->SHIPPING_PRICE_NET = $SHIPPING_PRICE_NET;
        $this->TERMS_ACCEPTED = $TERMS_ACCEPTED;
        $this->TRS_AMT_GROSS = $TRS_AMT_GROSS;
        $this->TRS_AMT_NET = $TRS_AMT_NET;
        $this->TRS_CURRENCY = $TRS_CURRENCY;
        $this->TRS_DT = $TRS_DT;
        $this->TRS_ID = $TRS_ID;
        $this->TRS_VAT_PERC = $TRS_VAT_PERC;
    }

    /**
     * @return string
     */
    public function getAutoActivate()
    {
        return $this->AUTO_ACTIVATE;
    }

    /**
     * @param string $AUTO_ACTIVATE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setAutoActivate($AUTO_ACTIVATE)
    {
        $this->AUTO_ACTIVATE = $AUTO_ACTIVATE;

        return $this;
    }

    /**
     * @return string
     */
    public function getAutoCapture()
    {
        return $this->AUTO_CAPTURE;
    }

    /**
     * @param string $AUTO_CAPTURE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setAutoCapture($AUTO_CAPTURE)
    {
        $this->AUTO_CAPTURE = $AUTO_CAPTURE;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setOrderId($ORDER_ID)
    {
        $this->ORDER_ID = $ORDER_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->PAY_ID;
    }

    /**
     * @param string $PAY_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setPayId($PAY_ID)
    {
        $this->PAY_ID = $PAY_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayType()
    {
        return $this->PAY_TYPE;
    }

    /**
     * @param string $PAY_TYPE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setPayType($PAY_TYPE)
    {
        $this->PAY_TYPE = $PAY_TYPE;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabateGross()
    {
        return $this->RABATE_GROSS;
    }

    /**
     * @param string $RABATE_GROSS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setRabateGross($RABATE_GROSS)
    {
        $this->RABATE_GROSS = $RABATE_GROSS;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabateNet()
    {
        return $this->RABATE_NET;
    }

    /**
     * @param string $RABATE_NET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setRabateNet($RABATE_NET)
    {
        $this->RABATE_NET = $RABATE_NET;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefNo()
    {
        return $this->REF_NO;
    }

    /**
     * @param string $REF_NO
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setRefNo($REF_NO)
    {
        $this->REF_NO = $REF_NO;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingName()
    {
        return $this->SHIPPING_NAME;
    }

    /**
     * @param string $SHIPPING_NAME
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setShippingName($SHIPPING_NAME)
    {
        $this->SHIPPING_NAME = $SHIPPING_NAME;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingPriceGross()
    {
        return $this->SHIPPING_PRICE_GROSS;
    }

    /**
     * @param string $SHIPPING_PRICE_GROSS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setShippingPriceGross($SHIPPING_PRICE_GROSS)
    {
        $this->SHIPPING_PRICE_GROSS = $SHIPPING_PRICE_GROSS;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingPriceNet()
    {
        return $this->SHIPPING_PRICE_NET;
    }

    /**
     * @param string $SHIPPING_PRICE_NET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setShippingPriceNet($SHIPPING_PRICE_NET)
    {
        $this->SHIPPING_PRICE_NET = $SHIPPING_PRICE_NET;

        return $this;
    }

    /**
     * @return string
     */
    public function getTermsAccepted()
    {
        return $this->TERMS_ACCEPTED;
    }

    /**
     * @param string $TERMS_ACCEPTED
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTermsAccepted($TERMS_ACCEPTED)
    {
        $this->TERMS_ACCEPTED = $TERMS_ACCEPTED;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsAmtGross()
    {
        return $this->TRS_AMT_GROSS;
    }

    /**
     * @param string $TRS_AMT_GROSS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsAmtGross($TRS_AMT_GROSS)
    {
        $this->TRS_AMT_GROSS = $TRS_AMT_GROSS;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsAmtNet()
    {
        return $this->TRS_AMT_NET;
    }

    /**
     * @param string $TRS_AMT_NET
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsAmtNet($TRS_AMT_NET)
    {
        $this->TRS_AMT_NET = $TRS_AMT_NET;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsCurrency($TRS_CURRENCY)
    {
        $this->TRS_CURRENCY = $TRS_CURRENCY;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsDt()
    {
        return $this->TRS_DT;
    }

    /**
     * @param string $TRS_DT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsDt($TRS_DT)
    {
        $this->TRS_DT = $TRS_DT;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsID()
    {
        return $this->TRS_ID;
    }

    /**
     * @param string $TRS_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsID($TRS_ID)
    {
        $this->TRS_ID = $TRS_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrsVatPerc()
    {
        return $this->TRS_VAT_PERC;
    }

    /**
     * @param string $TRS_VAT_PERC
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTrsVatPerc($TRS_VAT_PERC)
    {
        $this->TRS_VAT_PERC = $TRS_VAT_PERC;

        return $this;
    }

    /**
     * @return string
     */
    public function getTotalGrossCalcMethod()
    {
        return $this->TOTAL_GROSS_CALC_METHOD;
    }

    /**
     * @param string $TOTAL_GROSS_CALC_METHOD
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal
     */
    public function setTotalGrossCalcMethod($TOTAL_GROSS_CALC_METHOD)
    {
        $this->TOTAL_GROSS_CALC_METHOD = $TOTAL_GROSS_CALC_METHOD;

        return $this;
    }
}
