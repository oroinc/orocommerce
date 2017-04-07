<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveEntityInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItem;

interface ApruveOrderInterface extends ApruveEntityInterface
{
    /**
     * @return string
     */
    public function getMerchantId();

    /**
     * @return string
     */
    public function getShopperId();

    /**
     * @return int
     */
    public function getMerchantOrderId();

    /**
     * @return int
     */
    public function getAmountCents();

    /**
     * @return int
     */
    public function getTaxCents();

    /**
     * @return int
     */
    public function getShippingCents();

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @return ApruveLineItem[]
     */
    public function getLineItems();

    /**
     * @param bool $state
     *
     * @return self
     */
    public function setFinalizeOnCreate($state);

    /**
     * @return bool
     */
    public function getFinalizeOnCreate();

    /**
     * @param bool $state
     *
     * @return self
     */
    public function setInvoiceOnCreate($state);

    /**
     * @return bool
     */
    public function getInvoiceOnCreate();
}
