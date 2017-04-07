<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntity;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemInterface;

class ApruveOrder extends AbstractApruveEntity implements ApruveOrderInterface
{
    const MERCHANT_ID = 'merchant_id';

    /**
     * Required for offline (created manually via Apruve API) orders only.
     */
    const SHOPPER_ID = 'shopper_id';

    const MERCHANT_ORDER_ID = 'merchant_order_id';

    const AMOUNT_CENTS = 'amount_cents';
    const TAX_CENTS = 'tax_cents';
    const SHIPPING_CENTS = 'shipping_cents';
    const CURRENCY = 'currency';

    const LINE_ITEMS = 'order_items';

    const FINALIZE_ON_CREATE = 'finalize_on_create';
    const INVOICE_ON_CREATE = 'invoice_on_create';

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result[self::LINE_ITEMS] = $this->getPlainLineItems();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantOrderId()
    {
        return (int) $this->get(self::MERCHANT_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantId()
    {
        return (string) $this->get(self::MERCHANT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getShopperId()
    {
        return (string) $this->get(self::SHOPPER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmountCents()
    {
        return (int) $this->get(self::AMOUNT_CENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingCents()
    {
        return (int) $this->get(self::SHIPPING_CENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxCents()
    {
        return (int) $this->get(self::TAX_CENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return (string) $this->get(self::CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return (array) $this->get(self::LINE_ITEMS);
    }

    /**
     * {@inheritdoc}
     */
    public function setFinalizeOnCreate($state)
    {
        $this->set(self::FINALIZE_ON_CREATE, $state);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvoiceOnCreate()
    {
        return $this->get(self::INVOICE_ON_CREATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setInvoiceOnCreate($state)
    {
        $this->set(self::INVOICE_ON_CREATE, $state);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFinalizeOnCreate()
    {
        return $this->get(self::FINALIZE_ON_CREATE);
    }

    /**
     * @return array
     */
    protected function getPlainLineItems()
    {
        return array_map([$this, 'normalizeLineItem'], $this->getLineItems());
    }

    /**
     * @param ApruveLineItemInterface $lineItem
     *
     * @return array
     */
    protected function normalizeLineItem(ApruveLineItemInterface $lineItem)
    {
        return $lineItem->toArray();
    }
}
