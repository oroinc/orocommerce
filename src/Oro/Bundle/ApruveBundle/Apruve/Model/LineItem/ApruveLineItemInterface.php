<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveEntityInterface;

interface ApruveLineItemInterface extends ApruveEntityInterface
{
    /**
     * @return int
     */
    public function getPriceEaCents();

    /**
     * @return int
     */
    public function getQuantity();

    /**
     * @return int
     */
    public function getPriceTotalCents();

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @return string
     */
    public function getSku();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return string
     */
    public function getVendor();

    /**
     * @return string
     */
    public function getViewProductUrl();

    /**
     * @return string
     */
    public function getMerchantNotes();
}
