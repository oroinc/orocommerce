<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * Add an organization to tax classes
 */
class LoadProductTaxCodeOrganizationData extends AbstractFixture
{
    /**
     * @param ObjectManager|EntityManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $this->getFirstOrganization($manager);
        $manager
            ->createQueryBuilder()
            ->update(ProductTaxCode::class, 'product_tax_code')
            ->set('product_tax_code.organization', ':organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->execute();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization|object
     */
    private function getFirstOrganization(ObjectManager $manager): Organization
    {
        /** @var ArrayCollection $organizations */
        $organizations = $manager->getRepository(Organization::class)->findAll();

        return reset($organizations);
    }
}
