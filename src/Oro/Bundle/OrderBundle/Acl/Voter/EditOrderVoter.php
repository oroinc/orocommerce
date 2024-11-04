<?php

namespace Oro\Bundle\OrderBundle\Acl\Voter;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents modification of closed and cancelled orders.
 */
class EditOrderVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::EDIT];

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var Order|null $order */
        $order = $this->doctrineHelper->getEntityManagerForClass($class)->find($class, $identifier);
        if (null === $order) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->isOrderEditable($order)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function isOrderEditable(Order $order): bool
    {
        $internalStatusId = $order->getInternalStatus()?->getInternalId();
        if (!$internalStatusId) {
            return true;
        }

        return
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED !== $internalStatusId
            && OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED !== $internalStatusId;
    }
}
