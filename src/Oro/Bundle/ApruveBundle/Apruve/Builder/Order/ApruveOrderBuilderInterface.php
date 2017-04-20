<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderInterface;

interface ApruveOrderBuilderInterface
{
    /**
     * @return ApruveOrderInterface
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
     * @param \DateTime $dateTime
     *
     * @return ApruveOrderBuilderInterface
     */
    public function setExpireAt(\DateTime $dateTime);

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setAutoEscalate($bool);

    /**
     * @param string $poNumber
     *
     * @return ApruveOrderBuilderInterface
     */
    public function setPoNumber($poNumber);
}
