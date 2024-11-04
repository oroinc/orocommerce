<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;

/**
 * Grants "CLOSE_ORDERS" and "CANCEL_ORDERS" permissions for the following roles:
 * * ROLE_USER
 * * ROLE_MANAGER
 * * ROLE_SALES_ASSISTANT
 */
class UpdateCloseOrdersPermissions extends AbstractUpdatePermissions
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $this->getRoles($manager);
        if ($roles) {
            foreach ($roles as $role) {
                $this->setEntityPermissions(
                    $aclManager,
                    $role,
                    Order::class,
                    ['CANCEL_ORDERS_SYSTEM', 'CLOSE_ORDERS_SYSTEM']
                );
            }
            $aclManager->flush();
        }
    }

    private function getRoles(ObjectManager $manager): array
    {
        $roles = [];
        $role = $this->getRole($manager, 'ROLE_USER');
        if (null !== $role) {
            $roles[] = $role;
        }
        $role = $this->getRole($manager, 'ROLE_MANAGER');
        if (null !== $role) {
            $roles[] = $role;
        }
        $role = $this->getRole($manager, 'ROLE_SALES_ASSISTANT');
        if (null !== $role) {
            $roles[] = $role;
        }

        return $roles;
    }
}
