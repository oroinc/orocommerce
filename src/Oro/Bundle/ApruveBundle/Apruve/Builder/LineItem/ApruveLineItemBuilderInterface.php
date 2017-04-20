<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\LineItem\ApruveLineItemInterface;

interface ApruveLineItemBuilderInterface
{
    /**
     * @return ApruveLineItemInterface
     */
    public function getResult();

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title);

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
}
