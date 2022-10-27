<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadUserRolesData;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Updates permissions for Coupon entity for ROLE_USER role.
 */
class LoadCouponPermissionsForExistingRoles extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserRolesData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $this->setEntityPermissions(
            $aclManager,
            $this->getRole($manager, User::ROLE_DEFAULT),
            Coupon::class,
            ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM']
        );
        $aclManager->flush();
    }
}
