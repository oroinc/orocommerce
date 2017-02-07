<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\OrderBundle\Entity\Order;

interface DPDHandlerInterface
{
    /**
     * @return string|int
     */
    public function getIdentifier();

    /**
     * @param Order          $order
     * @param \DateTime|null $shipDate
     *
     * @return null|SetOrderResponse
     */
    public function shipOrder(Order $order, \DateTime $shipDate = null);

    /**
     * @param \DateTime|null $shipDate
     *
     * @return \DateTime
     */
    public function getNextPickupDay(\DateTime $shipDate = null);

    /**
     * @return ZipCodeRulesResponse
     */
    public function fetchZipCodeRules();
}
