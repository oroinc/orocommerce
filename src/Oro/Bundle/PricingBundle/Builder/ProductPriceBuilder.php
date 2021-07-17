<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Builder for product prices
 */
class ProductPriceBuilder implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ShardQueryExecutorInterface
     */
    protected $shardInsertQueryExecutor;

    /**
     * @var PriceListRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var int|null
     */
    private $version;

    /**
     * @var int
     */
    private $batchSize = ProductPriceRepository::BUFFER_SIZE;

    public function __construct(
        ManagerRegistry $registry,
        ShardQueryExecutorInterface $shardInsertQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->shardInsertQueryExecutor = $shardInsertQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
    }

    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function setShardInsertQueryExecutor(ShardQueryExecutorInterface $shardInsertQueryExecutor)
    {
        $this->shardInsertQueryExecutor = $shardInsertQueryExecutor;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceList(PriceList $priceList, array $products = [])
    {
        if (!$products) {
            $this->version = time();
        }
        $this->buildByPriceListWithoutTriggers($priceList, $products);

        if ($this->isFeaturesEnabled()) {
            $this->emitCplTriggers($priceList, $products);
        }

        $this->version = null;
    }

    /**
     * @param PriceRule $priceRule
     * @param array|Product[] $products
     */
    protected function applyRule(PriceRule $priceRule, array $products = [])
    {
        $fields = $this->ruleCompiler->getOrderedFields();
        $qb = $this->ruleCompiler->compile($priceRule, $products);
        if ($this->version) {
            $fields[] = 'version';
            $qb->addSelect((string)$qb->expr()->literal($this->version));
        }

        $this->shardInsertQueryExecutor->execute(ProductPrice::class, $fields, $qb);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        if (!$this->productPriceRepository) {
            $this->productPriceRepository = $this->registry
                ->getManagerForClass(ProductPrice::class)
                ->getRepository(ProductPrice::class);
        }

        return $this->productPriceRepository;
    }

    /**
     * @param PriceList $priceList
     * @return array
     */
    protected function getSortedRules(PriceList $priceList)
    {
        $rules = $priceList->getPriceRules()->toArray();
        usort(
            $rules,
            static function (PriceRule $a, PriceRule $b) {
                return $a->getPriority() <=> $b->getPriority();
            }
        );

        return $rules;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function buildByPriceListWithoutTriggers(PriceList $priceList, array $products = [])
    {
        foreach ($this->getProductBatches($products) as $productBatch) {
            $this->getProductPriceRepository()->deleteGeneratedPrices($this->shardManager, $priceList, $productBatch);
            if (count($priceList->getPriceRules()) > 0) {
                $rules = $this->getSortedRules($priceList);
                foreach ($rules as $rule) {
                    $this->applyRule($rule, $productBatch);
                }
            }
        }
    }

    private function emitCplTriggers(PriceList $priceList, array $products): void
    {
        if ($products || count($priceList->getPriceRules()) === 0) {
            $productsBatches = $this->getProductBatches($products);
        } else {
            $productsBatches = $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $this->version,
                $this->batchSize
            );
        }

        foreach ($productsBatches as $batch) {
            $this->priceListTriggerHandler->handlePriceListTopic(
                Topics::RESOLVE_COMBINED_PRICES,
                $priceList,
                $batch
            );
        }
    }

    private function getProductBatches(array $products): \Generator
    {
        if (!$products) {
            yield [];
        } else {
            foreach (array_chunk($products, $this->batchSize) as $batch) {
                yield $batch;
            }
        }
    }
}
