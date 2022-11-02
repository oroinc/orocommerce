<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Updates payment permissions for Order entity for ROLE_ADMINISTRATOR role.
 */
class LoadPaymentPermissionsRolesData extends AbstractUpdatePermissions
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $this->setEntityPermissions(
            $aclManager,
            $this->getRole($manager, User::ROLE_ADMINISTRATOR),
            Order::class,
            ['VIEW_PAYMENT_HISTORY_SYSTEM', 'CHARGE_AUTHORIZED_PAYMENTS_SYSTEM']
        );
        $aclManager->flush();
    }
}
