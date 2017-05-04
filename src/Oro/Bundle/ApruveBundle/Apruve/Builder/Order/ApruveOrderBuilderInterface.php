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
     * Create Apruve invoice when order is created.
     *
     * Defaults to true when not passed to Apruve.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function setInvoiceOnCreate($bool);

    /**
     * Finalize order when order is created.
     *
     * Defaults to true when not passed to Apruve.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function setFinalizeOnCreate($bool);

    /**
     * The unique identifier of the User who placed this order.
     *
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
     * ISO8601 date that a pending order should expire.
     *
     * Any order still unapproved at this date will be automatically rejected.
     * This field does not apply once payment terms have started negotiation.
     *
     * @param string $expireAt
     *
     * @return self
     */
    public function setExpireAt($expireAt);

    /**
     * Parameter "auto_escalate" is a convenience parameter which, when supplied will set
     * the "finalize_on_create" and "invoice_on_create" values to whatever the "auto_escalate" value is.
     * It really isn't used much any more and it isn't persisted on the order.
     *
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
