<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;

interface ApruveInvoiceBuilderInterface
{
    /**
     * @return ApruveInvoice
     */
    public function getResult();

    /**
     * Issue invoice when invoice is created.
     *
     * If true, the Invoice will be issued to the buyer as soon as it's created.
     * If false, the invoice will not be issued until you explicitly use the Issue action on it.
     * When not passed to Apruve - defaults to true.
     *
     * @param bool $bool
     *
     * @return self
     */
    public function setIssueOnCreate($bool);

    /**
     * ISO8601 date that payment in full is due.
     *
     * When not passed to Apruve - defaults to immediately.
     *
     * @param string $dueAt
     *
     * @return self
     */
    public function setDueAt($dueAt);

    /**
     * @param string|int $invoiceId
     *
     * @return self
     */
    public function setMerchantInvoiceId($invoiceId);

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

    /**
     * @param string $notes
     *
     * @return self
     */
    public function setMerchantNotes($notes);
}
