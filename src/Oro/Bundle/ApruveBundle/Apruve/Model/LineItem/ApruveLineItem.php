<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntity;

class ApruveLineItem extends AbstractApruveEntity implements ApruveLineItemInterface
{
    const PRICE_TOTAL_CENTS = 'price_total_cents';
    const PRICE_EA_CENTS = 'price_ea_cents';
    const QUANTITY = 'quantity';
    const CURRENCY = 'currency';
    const SKU = 'sku';
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const VIEW_PRODUCT_URL = 'view_product_url';
    const VENDOR = 'vendor';
    const MERCHANT_NOTES = 'merchant_notes';

    /**
     * {@inheritdoc}
     */
    public function getPriceEaCents()
    {
        return (int)$this->get(self::PRICE_EA_CENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity()
    {
        return (int)$this->get(self::QUANTITY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceTotalCents()
    {
        return (int)$this->get(self::PRICE_TOTAL_CENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return (string)$this->get(self::CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function getSku()
    {
        return (string)$this->get(self::SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return (string)$this->get(self::TITLE);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return (string)$this->get(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function getVendor()
    {
        return (string)$this->get(self::VENDOR);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewProductUrl()
    {
        return (string)$this->get(self::VIEW_PRODUCT_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantNotes()
    {
        return (string)$this->get(self::MERCHANT_NOTES);
    }
}
