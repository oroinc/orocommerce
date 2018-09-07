<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Load default ACL permissions for All roles (except Anonymous) on ProductPrice entity
 * It's will run in case when application already installed
 * to prevent issue with blocking managing of ProductPrice entity to current users
 */
class LoadProductPriceAclPermissions extends AbstractLoadAclData implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        return parent::load($manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataPath()
    {
        return '@OroPricingBundle/Migrations/Data/ORM/data/product_price_acl_permissions.yml';
    }
}
