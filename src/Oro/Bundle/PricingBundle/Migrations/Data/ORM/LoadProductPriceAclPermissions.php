<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Load default ACL permissions for All roles (except Anonymous) on ProductPrice entity
 * It's will run in case when application already installed
 * to prevent issue with blocking managing of ProductPrice entity to current users
 */
class LoadProductPriceAclPermissions extends AbstractLoadAclData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        parent::load($manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataPath()
    {
        return '@OroPricingBundle/Migrations/Data/ORM/data/product_price_acl_permissions.yml';
    }
}
