<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\InsertQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Builder for relations between price lists and products
 */
class PriceListProductAssignmentBuilder
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertQueryExecutorInterface
     */
    protected $insertQueryExecutor;

    /**
     * @var ProductAssignmentRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        ManagerRegistry $registry,
        InsertQueryExecutorInterface $insertQueryExecutor,
        ProductAssignmentRuleCompiler $ruleCompiler,
        EventDispatcherInterface $eventDispatcher,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->insertQueryExecutor = $insertQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->eventDispatcher = $eventDispatcher;
        $this->shardManager = $shardManager;
    }

    public function setInsertQueryExecutor(InsertQueryExecutorInterface $insertQueryExecutor)
    {
        $this->insertQueryExecutor = $insertQueryExecutor;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceList(PriceList $priceList, array $products = [])
    {
        $this->buildByPriceListWithoutEventDispatch($priceList, $products);

        $event = new AssignmentBuilderBuildEvent($priceList, $products);
        $this->eventDispatcher->dispatch($event, AssignmentBuilderBuildEvent::NAME);
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    protected function clearGenerated(PriceList $priceList, array $products = [])
    {
        /** @var PriceListToProductRepository $repo */
        $repo = $this->registry->getManagerForClass(PriceListToProduct::class)
            ->getRepository(PriceListToProduct::class);
        $repo->deleteGeneratedRelations($priceList, $products);
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceListWithoutEventDispatch(PriceList $priceList, array $products = [])
    {
        $this->clearGenerated($priceList, $products);
        if ($priceList->getProductAssignmentRule()) {
            $this->insertQueryExecutor->execute(
                PriceListToProduct::class,
                $this->ruleCompiler->getOrderedFields(),
                $this->ruleCompiler->compile($priceList, $products)
            );
        }
        $this->registry->getManagerForClass(ProductPrice::class)
            ->getRepository(ProductPrice::class)
            ->deleteInvalidPricesByProducts($this->shardManager, $priceList, $products);
    }
}
