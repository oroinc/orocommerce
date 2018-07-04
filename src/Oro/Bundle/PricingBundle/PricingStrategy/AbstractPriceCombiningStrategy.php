<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectExecutorAwareInterface;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractPriceCombiningStrategy implements
    PriceCombiningStrategyInterface,
    InsertFromSelectExecutorAwareInterface,
    PriceCombiningStrategyFallbackAwareInterface
{

    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var CombinedPriceListToPriceListRepository
     */
    protected $combinedPriceListRelationsRepository;

    /**
     * @var CombinedProductPriceRepository
     */
    protected $combinedProductPriceRepository;
    /**
     * @var array
     */
    protected $builtList = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param Registry $registry
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        Registry $registry,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return bool
     */
    protected function isOutputEnabled()
    {
        return $this->output !== null && $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    }

    /**
     * {@inheritdoc}
     */
    public function combinePrices(CombinedPriceList $combinedPriceList, array $products = [], $startTimestamp = null)
    {
        if (!$products
            && $startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }
        $priceListsRelations = $this->getCombinedPriceListRelationsRepository()
            ->getPriceListRelations(
                $combinedPriceList,
                $products
            );

        $progressBar = null;
        if ($this->isOutputEnabled()) {
            $this->output->writeln(
                'Processing combined price list id: '.$combinedPriceList->getId().' - '.$combinedPriceList->getName(),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $progressBar = new ProgressBar($this->output, \count($priceListsRelations));
        }
        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList, $products);

        $this->processPriceLists($combinedPriceList, $priceListsRelations, $products, $progressBar);

        if (!$products) {
            $combinedPriceList->setPricesCalculated(true);
            $this->getManager()->flush($combinedPriceList);
        }

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $this->output->writeln(
                '<info> - Finished processing combined price list id: '.$combinedPriceList->getId().'</info>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $this->triggerHandler->processByProduct($combinedPriceList, $products);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function combinePricesUsingPrecalculatedFallback(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        CombinedPriceList $fallbackLevelCpl,
        $startTimestamp = null
    ) {
        if ($startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }

        $progressBar = null;
        if ($this->isOutputEnabled()) {
            $this->output->writeln(
                'Processing combined price list id: '.$combinedPriceList->getId().' - '.$combinedPriceList->getName(),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $progressBar = new ProgressBar($this->output, \count($priceLists) + 1);
        }

        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList);

        $priceListRelations = $this->getPriceListRelationsBySequenceMembers($combinedPriceList, $priceLists);
        $this->processPriceLists($combinedPriceList, $priceListRelations, [], $progressBar);

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $progressBar->clear();
            $this->output->writeln(
                'Applying combined price: '.$fallbackLevelCpl->getId().' - '.$fallbackLevelCpl->getName(),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            $progressBar->display();
        }
        $this->processCombinedPriceListRelation($combinedPriceList, $fallbackLevelCpl);

        $combinedPriceList->setPricesCalculated(true);
        $this->getManager()->flush($combinedPriceList);

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $this->output->writeln(
                '<info> - Finished processing combined price list id: '.$combinedPriceList->getId().'</info>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $this->triggerHandler->processByProduct($combinedPriceList);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array $priceLists
     * @param array $products
     * @param ProgressBar|null $progressBar
     */
    protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        array $products = [],
        ProgressBar $progressBar = null
    ) {
        $progress = 0;
        foreach ($priceLists as $priceListRelation) {
            if ($this->isOutputEnabled()) {
                if ($progressBar) {
                    $progressBar->setProgress(++$progress);
                    $progressBar->clear();
                }
                $this->output->writeln(
                    'Processing price list: ' . $priceListRelation->getPriceList()->getName(),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
                if ($progressBar) {
                    $progressBar->display();
                }
            }
            $this->processRelation($combinedPriceList, $priceListRelation, $products);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $className = 'OroPricingBundle:CombinedPriceList';
            $this->manager = $this->registry
                ->getManagerForClass($className);
        }

        return $this->manager;
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListRelationsRepository()
    {
        if (!$this->combinedPriceListRelationsRepository) {
            $priceListRelationClassName = 'OroPricingBundle:CombinedPriceListToPriceList';
            $this->combinedPriceListRelationsRepository = $this->registry
                ->getManagerForClass($priceListRelationClassName)
                ->getRepository($priceListRelationClassName);
        }

        return $this->combinedPriceListRelationsRepository;
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getCombinedProductPriceRepository()
    {
        if (!$this->combinedProductPriceRepository) {
            $combinedPriceClassName = 'OroPricingBundle:CombinedProductPrice';
            $this->combinedProductPriceRepository = $this->registry
                ->getManagerForClass($combinedPriceClassName)
                ->getRepository($combinedPriceClassName);
        }

        return $this->combinedProductPriceRepository;
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->builtList = [];

        return $this;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param CombinedPriceListToPriceList $priceListRelation
     * @param array|Product[] $products
     */
    abstract protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    );

    /**
     * {@inheritDoc}
     */
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor)
    {
        $this->insertFromSelectQueryExecutor = $queryExecutor;
    }

    /**
     * {@inheritDoc}
     */
    public function getInsertSelectExecutor()
    {
        return $this->insertFromSelectQueryExecutor;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|PriceListSequenceMember[] $priceLists
     * @return array
     */
    protected function getPriceListRelationsBySequenceMembers(
        CombinedPriceList $combinedPriceList,
        array $priceLists
    ): array {
        $priceListRelations = [];
        foreach ($priceLists as $key => $sequenceMember) {
            $relation = new CombinedPriceListToPriceList();
            $relation->setCombinedPriceList($combinedPriceList);
            $relation->setPriceList($sequenceMember->getPriceList());
            $relation->setMergeAllowed($sequenceMember->isMergeAllowed());
            $relation->setSortOrder($key);

            $priceListRelations[] = $relation;
        }

        return $priceListRelations;
    }
}
