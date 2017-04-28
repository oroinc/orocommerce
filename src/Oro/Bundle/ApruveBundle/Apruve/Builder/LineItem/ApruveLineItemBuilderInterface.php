<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;

interface ApruveLineItemBuilderInterface
{
    /**
     * @return ApruveLineItem
     */
    public function getResult();

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description);

    /**
     * @param string $url
     *
     * @return self
     */
    public function setViewProductUrl($url);

    /**
     * @param string $notes
     *
     * @return self
     */
    public function setMerchantNotes($notes);

    /**
     * @param string $vendor
     *
     * @return self
     */
    public function setVendor($vendor);

    /**
     * @param string $info
     *
     * @return self
     */
    public function setVariantInfo($info);

    /**
     * @param string $sku
     *
     * @return self
     */
    public function setSku($sku);

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setEaCents($amount);
}
