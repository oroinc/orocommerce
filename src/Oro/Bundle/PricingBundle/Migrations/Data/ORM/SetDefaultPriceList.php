<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets default price list into configuration
 * and executes combined price lists rebuild
 */
class SetDefaultPriceList extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceListData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var PriceListRepository $repository */
        $repository = $this->container->get('doctrine')->getRepository(PriceList::class);
        $defaultPriceList = $repository->getDefault();

        $configManager = $this->container->get('oro_config.global');
        $configManager->set(
            'oro_pricing.default_price_lists',
            [new PriceListConfig($defaultPriceList, 100, true)]
        );
        $configManager->flush();

        $this->rebuildCombinedPrices();
    }

    private function rebuildCombinedPrices(): void
    {
        /** @var CombinedPriceListAssociationsProvider $associationsProvider */
        $associationsProvider = $this->container->get('oro_pricing.combined_price_list_associations_provider');
        /** @var CombinedPriceListProvider $cplProvider */
        $cplProvider = $this->container->get('oro_pricing.provider.combined_price_list');
        /** @var CombinedPriceListsBuilderFacade $cplBuilderFacade */
        $cplBuilderFacade = $this->container->get('oro_pricing.builder.combined_price_list_builder_facade');

        $associations = $associationsProvider->getCombinedPriceListsWithAssociations();
        foreach ($associations as $association) {
            $cpl = $cplProvider->getCombinedPriceListByCollectionInformation($association['collection']);
            $cplBuilderFacade->rebuild([$cpl]);
            $assignTo = $association['assign_to'] ?? [];
            if (!empty($assignTo)) {
                $cplBuilderFacade->processAssignments($cpl, $assignTo, null, true);
            }
            $cplBuilderFacade->triggerProductIndexation($cpl, $assignTo);
        }
    }
}
