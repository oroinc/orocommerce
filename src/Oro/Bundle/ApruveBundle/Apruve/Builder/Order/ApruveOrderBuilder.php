<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

class ApruveOrderBuilder implements ApruveOrderBuilderInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var int
     */
    private $amountCents;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var array
     */
    private $lineItems;

    /**
     * @param string $merchantId
     * @param int    $amountCents
     * @param string $currency
     * @param array  $lineItems
     */
    public function __construct($merchantId, $amountCents, $currency, array $lineItems)
    {
        $this->merchantId = $merchantId;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->lineItems = $lineItems;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            ApruveOrder::MERCHANT_ID => (string)$this->merchantId,
            ApruveOrder::AMOUNT_CENTS => (int)$this->amountCents,
            ApruveOrder::CURRENCY => (string)$this->currency,
            ApruveOrder::LINE_ITEMS => (array)$this->lineItems,
        ];

        return new ApruveOrder($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function setInvoiceOnCreate($bool)
    {
        $this->data[ApruveOrder::INVOICE_ON_CREATE] = (bool)$bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFinalizeOnCreate($bool)
    {
        $this->data[ApruveOrder::FINALIZE_ON_CREATE] = (bool)$bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShopperId($id)
    {
        $this->data[ApruveOrder::SHOPPER_ID] = (string)$id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCorporateAccountId($id)
    {
        $this->data[ApruveOrder::PAYMENT_TERM_PARAMS][ApruveOrder::_CORPORATE_ACCOUNT_ID] = (string)$id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setExpireAt($expireAt)
    {
        $this->data[ApruveOrder::EXPIRE_AT] = (string)$expireAt;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAutoEscalate($bool)
    {
        $this->data[ApruveOrder::AUTO_ESCALATE] = (bool)$bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPoNumber($poNumber)
    {
        $this->data[ApruveOrder::PO_NUMBER] = (string)$poNumber;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantOrderId($orderId)
    {
        $this->data[ApruveOrder::MERCHANT_ORDER_ID] = (string)$orderId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingCents($amount)
    {
        $this->data[ApruveOrder::SHIPPING_CENTS] = (int)$amount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxCents($amount)
    {
        $this->data[ApruveOrder::TAX_CENTS] = (int)$amount;

        return $this;
    }
}
