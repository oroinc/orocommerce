<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
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

    protected ShardManager $shardManager;
    protected ManagerRegistry $registry;
    protected ShardQueryExecutorInterface $shardInsertQueryExecutor;
    protected PriceListRuleCompiler $ruleCompiler;
    protected PriceListTriggerHandler $priceListTriggerHandler;
    protected CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler;
    private ?ProductPriceRepository $productPriceRepository = null;
    private ?int $version = null;

    /**
     * @var int
     */
    private $batchSize = ProductPriceRepository::BUFFER_SIZE;

    public function __construct(
        ManagerRegistry $registry,
        ShardQueryExecutorInterface $shardInsertQueryExecutor,
        PriceListRuleCompiler $ruleCompiler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager,
        CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler
    ) {
        $this->registry = $registry;
        $this->shardInsertQueryExecutor = $shardInsertQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
        $this->combinedPriceListBuildTriggerHandler = $combinedPriceListBuildTriggerHandler;
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
        $priceRulesCount = count($priceList->getPriceRules());

        // In some cases, we cannot guarantee that a combined price list includes a specific price list.
        // This problem arises if prices are generated dynamically, and we do not know whether it is possible to
        // include this price list in the combined price list until prices are generated.
        // After generating prices need to send message to rebuild the combined price list.
        if ($priceRulesCount > 0 && $this->combinedPriceListBuildTriggerHandler->handle($priceList)) {
            return;
        }

        if ($products || $priceRulesCount === 0) {
            $productsBatches = $this->getProductBatches($products);
        } else {
            $productsBatches = $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList->getId(),
                $this->version,
                $this->batchSize
            );
        }

        foreach ($productsBatches as $batch) {
            $this->priceListTriggerHandler->handlePriceListTopic(
                ResolveCombinedPriceByPriceListTopic::getName(),
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
