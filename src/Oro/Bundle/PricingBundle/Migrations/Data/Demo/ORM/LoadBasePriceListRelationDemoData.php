<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Provides demo fixture loading for price list relation.
 */
abstract class LoadBasePriceListRelationDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    private ?array $websites = null;
    private ?array $priceLists = null;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomerDemoData::class,
            LoadCustomerGroupDemoData::class,
            LoadPriceListDemoData::class
        ];
    }

    protected function getFileLocator(): FileLocatorInterface
    {
        return $this->container->get('file_locator');
    }

    protected function getPriceListByName(ObjectManager $manager, string $name): PriceList
    {
        foreach ($this->getPriceLists($manager) as $priceList) {
            if ($priceList->getName() === $name) {
                return $priceList;
            }
        }

        throw new \LogicException(sprintf('There is no priceList with name "%s" .', $name));
    }

    protected function getWebsiteByName(ObjectManager $manager, string $name): Website
    {
        foreach ($this->getWebsites($manager) as $website) {
            if ($website->getName() === $name) {
                return $website;
            }
        }

        throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
    }

    protected function getWebsites(ObjectManager $manager): array
    {
        if (null === $this->websites) {
            $this->websites = $manager->getRepository(Website::class)->findAll();
        }

        return $this->websites;
    }

    protected function getPriceLists(ObjectManager $manager): array
    {
        if (!$this->priceLists) {
            $this->priceLists = $manager->getRepository(PriceList::class)->findAll();
        }

        return $this->priceLists;
    }
}
