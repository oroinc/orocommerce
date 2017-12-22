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
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractPriceCombiningStrategy implements
    PriceCombiningStrategyInterface,
    InsertFromSelectExecutorAwareInterface
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
     * @param CombinedPriceList $combinedPriceList
     * @param Product|null $product
     * @param int|null $startTimestamp
     */
    public function combinePrices(CombinedPriceList $combinedPriceList, Product $product = null, $startTimestamp = null)
    {
        if ($product === null
            && $startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }
        $priceListsRelations = $this->getCombinedPriceListRelationsRepository()
            ->getPriceListRelations(
                $combinedPriceList,
                $product
            );

        if ($this->isOutputEnabled()) {
            $this->output->writeln(
                'Processing combined price list id: '.$combinedPriceList->getId().' - '.$combinedPriceList->getName(),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $progressBar = new ProgressBar($this->output, count($priceListsRelations));
        }
        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList, $product);
        foreach ($priceListsRelations as $i => $priceListRelation) {
            if ($this->isOutputEnabled()) {
                $progressBar->setProgress($i);
                $progressBar->clear();
                $this->output->writeln(
                    'Processing price list: '.$priceListRelation->getPriceList()->getName(),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
                $progressBar->display();
            }
            $this->processRelation($combinedPriceList, $priceListRelation, $product);
        }
        if (!$product) {
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

        $this->triggerHandler->processByProduct($combinedPriceList, $product);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
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
     * @param Product|null $product
     */
    abstract protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        Product $product = null
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
}
