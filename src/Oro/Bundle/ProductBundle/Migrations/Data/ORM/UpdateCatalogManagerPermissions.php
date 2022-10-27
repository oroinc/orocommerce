<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadUserRolesData;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;

/**
 * Updates permissions for ROLE_CATALOG_MANAGER role.
 */
class UpdateCatalogManagerPermissions extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserRolesData::class];
    }

    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $role = $this->getRole($manager, 'ROLE_CATALOG_MANAGER');
        $this->setEntityPermissions(
            $aclManager,
            $role,
            AttributeFamily::class,
            ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM', 'ASSIGN_SYSTEM']
        );
        $this->enableActions(
            $aclManager,
            $role,
            [
                'oro_attribute_create',
                'oro_attribute_update',
                'oro_attribute_view',
                'oro_attribute_remove',
                'oro_related_products_edit',
                'oro_upsell_products_edit'
            ]
        );
        $aclManager->flush();
    }
}
