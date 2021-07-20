<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * Building all combined price lists during loading of demo data
 * Disables search re-indexation for building combined price lists
 */
class BuildPricesDemoDataFixturesListener extends AbstractDemoDataFixturesListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var CombinedPriceListsBuilderFacade CombinedPriceListsBuilderFacade */
    protected $combinedPriceListsBuilderFacade;

    /** @var ProductPriceBuilder */
    protected $priceBuilder;

    /** @var PriceListProductAssignmentBuilder */
    protected $assignmentBuilder;

    public function __construct(
        OptionalListenerManager $listenerManager,
        CombinedPriceListsBuilderFacade $builderFacade,
        ProductPriceBuilder $priceBuilder,
        PriceListProductAssignmentBuilder $assignmentBuilder
    ) {
        parent::__construct($listenerManager);

        $this->combinedPriceListsBuilderFacade = $builderFacade;
        $this->priceBuilder = $priceBuilder;
        $this->assignmentBuilder = $assignmentBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $event->log('building all combined price lists');

        // website search index should not be re-indexed while cpl build
        $this->listenerManager->disableListener('oro_website_search.reindex_request.listener');
        $this->buildPrices($event->getObjectManager());
        $this->listenerManager->enableListener('oro_website_search.reindex_request.listener');
    }

    protected function buildPrices(ObjectManager $manager)
    {
        $priceLists = $manager->getRepository(PriceList::class)->getPriceListsWithRules();

        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
            $this->priceBuilder->buildByPriceListWithoutTriggers($priceList);
        }

        $this->combinedPriceListsBuilderFacade->rebuildAll(time());
    }
}
