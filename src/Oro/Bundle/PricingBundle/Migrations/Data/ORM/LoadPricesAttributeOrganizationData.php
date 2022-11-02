<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;

/**
 * Set default organization for price attribute
 */
class LoadPricesAttributeOrganizationData extends AbstractFixture
{
    /**
     * @param ObjectManager|EntityManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $this->getFirstOrganization($manager);
        $manager
            ->createQueryBuilder()
            ->update(PriceAttributePriceList::class, 'price_attribute')
            ->set('price_attribute.organization', ':organization')
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
