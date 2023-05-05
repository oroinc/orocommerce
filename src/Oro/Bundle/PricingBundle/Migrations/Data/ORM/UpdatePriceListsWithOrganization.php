<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets the first default organization to price lists without organization, adds default price lists to organizations
 * and reindex price lists.
 */
class UpdatePriceListsWithOrganization extends UpdateWithOrganization implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // the fixture should be applied only during update.
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $this->update($manager, PriceList::class, 'organization', true);
        $this->addDefaultPriceListsToOrganizations($manager);
        $manager->flush();

        $this->container->get('oro_search.async.indexer')->reindex(PriceList::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    private function addDefaultPriceListsToOrganizations(ObjectManager $manager)
    {
        $repo = $manager->getRepository(Organization::class);
        $firstOrgId = $repo->getFirst()->getId();
        $organizations = $repo->findAll();

        $currencies = $this->container->get('oro_currency.config.currency')->getCurrencyList();

        foreach ($organizations as $organization) {
            if ($firstOrgId === $organization->getId()) {
                continue;
            }

            $priceList = new PriceList();
            $priceList->setActive(true);
            $priceList->setName($organization->getName() . 'Price List');
            $priceList->setOrganization($organization);
            $priceList->setCurrencies($currencies);

            $manager->persist($priceList);
        }
    }
}
