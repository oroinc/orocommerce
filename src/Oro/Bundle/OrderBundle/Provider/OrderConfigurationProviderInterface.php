<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;

interface OrderConfigurationProviderInterface
{
    /**
     * Returns configured 'New Internal Order Status' config value
     *
     * @param Order $order
     *
     * @return string
     */
    public function getNewOrderInternalStatus(Order $order);

    /**
     * Returns true if 'Automatic Order Cancellation' is enabled for scope
     *
     * @param mixed $identifier
     *
     * @return string
     */
    public function isAutomaticCancellationEnabled($identifier = null);

    /**
     * Returns configured 'Target Status' config value
     *
     * @param mixed $identifier
     *
     * @return string
     */
    public function getTargetInternalStatus($identifier = null);

    /**
     * Returns configured 'Applicable Statuses' for given scope
     *
     * @param mixed $identifier
     *
     * @return array
     */
    public function getApplicableInternalStatuses($identifier = null);
}
