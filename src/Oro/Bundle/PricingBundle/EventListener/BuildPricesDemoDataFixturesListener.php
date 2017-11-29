<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class BuildPricesDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_pricing.entity_listener.product_price_cpl',
        'oro_pricing.entity_listener.price_list_to_product',
    ];

    /** @var CombinedPriceListsBuilder */
    protected $priceListBuilder;

    /** @var ProductPriceBuilder */
    protected $priceBuilder;

    /** @var PriceListProductAssignmentBuilder */
    protected $assignmentBuilder;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param CombinedPriceListsBuilder $priceListBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        CombinedPriceListsBuilder $priceListBuilder,
        ProductPriceBuilder $priceBuilder,
        PriceListProductAssignmentBuilder $assignmentBuilder
    ) {
        parent::__construct($listenerManager);

        $this->priceListBuilder = $priceListBuilder;
        $this->priceBuilder = $priceBuilder;
        $this->assignmentBuilder = $assignmentBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function onPostLoadActions(MigrationDataFixturesEvent $event)
    {
        $event->log('building all combined price lists');

        $this->buildPrices($event->getObjectManager());
    }

    /**
     * @param ObjectManager $manager
     */
    protected function buildPrices(ObjectManager $manager)
    {
        $priceLists = $manager->getRepository(PriceList::class)->getPriceListsWithRules();

        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceList($priceList);
            $this->priceBuilder->buildByPriceList($priceList);
        }

        $now = new \DateTime();
        $this->priceListBuilder->build($now->getTimestamp());
    }
}
