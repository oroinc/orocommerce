<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Migrations\Data\ORM\SetDefaultCurrencyFromLocale;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads Price List for the first organization.
 */
class LoadPriceListData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    public const PRICE_LIST_NAME = 'Default Price List';

    private ContainerInterface $container;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [SetDefaultCurrencyFromLocale::class];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $organization = $this->hasReference('default_organization')
            ? $this->getReference('default_organization')
            : $manager->getRepository(Organization::class)->getFirst();

        $priceList = $manager->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->where('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $priceList) {
            $priceList = new PriceList();
            $priceList
                ->setCurrencies($this->container->get('oro_currency.config.currency')->getCurrencyList())
                ->setOrganization($organization);
        }

        $priceList->setName(self::PRICE_LIST_NAME);
        $manager->persist($priceList);
        $manager->flush();

        $this->addReference('default_price_list', $priceList);
    }
}
