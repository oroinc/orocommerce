<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Request\Order\ApruveOrderRequestDataInterface;

interface ApruveOrderBuilderInterface
{
    /**
     * @return ApruveOrderRequestDataInterface
     */
    public function getResult();

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setInvoiceOnCreate($bool);

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setFinalizeOnCreate($bool);

    /**
     * @param int|float|string $amount
     *
     * @return self
     */
    public function setShippingAmount($amount);

    /**
     * @param int|float|string $amount
     *
     * @return self
     */
    public function setTaxAmount($amount);

    /**
     * @param string $id
     *
     * @return self
     */
    public function setShopperId($id);

    /**
     * @param int $id
     *
     * @return self
     */
    public function setMerchantOrderId($id);
}
