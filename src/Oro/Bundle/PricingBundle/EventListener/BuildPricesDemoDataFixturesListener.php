<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BuildPricesDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_pricing.entity_listener.product_price_cpl',
        'oro_pricing.entity_listener.price_list_to_product',
    ];

    /** @var OptionalListenerManager */
    protected $listenerManager;

    /** @var RegistryInterface */
    protected $doctrine;

    /** @var CombinedPriceListsBuilder */
    protected $priceListBuilder;

    /** @var ProductPriceBuilder */
    protected $priceBuilder;

    /** @var PriceListProductAssignmentBuilder */
    protected $assignmentBuilder;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param RegistryInterface $doctrine
     * @param CombinedPriceListsBuilder $priceListBuilder
     * @param ProductPriceBuilder $priceBuilder
     * @param PriceListProductAssignmentBuilder $assignmentBuilder
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        RegistryInterface $doctrine,
        CombinedPriceListsBuilder $priceListBuilder,
        ProductPriceBuilder $priceBuilder,
        PriceListProductAssignmentBuilder $assignmentBuilder
    ) {
        $this->listenerManager = $listenerManager;
        $this->doctrine = $doctrine;
        $this->priceListBuilder = $priceListBuilder;
        $this->priceBuilder = $priceBuilder;
        $this->assignmentBuilder = $assignmentBuilder;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->disableListeners(self::LISTENERS);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->enableListeners(self::LISTENERS);

        $event->log('building all combined price lists');

        $this->buildPrices();
    }

    protected function buildPrices()
    {
        $priceLists = $this->getPriceListRepository()->getPriceListsWithRules();

        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceList($priceList);
            $this->priceBuilder->buildByPriceList($priceList);
        }

        $now = new \DateTime();
        $this->priceListBuilder->build($now->getTimestamp());
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->doctrine->getManagerForClass(PriceList::class)->getRepository(PriceList::class);
    }
}
