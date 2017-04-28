<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

interface ApruveOrderBuilderInterface
{
    /**
     * @return ApruveOrder
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
     * @param string $id
     *
     * @return self
     */
    public function setShopperId($id);

    /**
     * @param string $id
     *
     * @return self
     */
    public function setCorporateAccountId($id);

    /**
     * @param string $expireAt
     *
     * @return self
     */
    public function setExpireAt($expireAt);

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setAutoEscalate($bool);

    /**
     * @param string $poNumber
     *
     * @return self
     */
    public function setPoNumber($poNumber);

    /**
     * @param string|int $orderId
     *
     * @return self
     */
    public function setMerchantOrderId($orderId);

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setShippingCents($amount);

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setTaxCents($amount);
}
