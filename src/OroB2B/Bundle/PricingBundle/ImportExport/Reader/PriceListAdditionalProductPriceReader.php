<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use OroB2B\Bundle\PricingBundle\ImportExport\Reader\Iterator\AdditionalProductPricesIterator;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class PriceListAdditionalProductPriceReader extends IteratorBasedReader
{
    /**
     * @var int
     */
    protected $priceListId;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param ContextRegistry $contextRegistry
     * @param ManagerRegistry $registry
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($contextRegistry);

        $this->registry = $registry;
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->priceListId = (int)$context->getOption('price_list_id');
        $this->setSourceIterator($this->createIterator());

        $configuration = $this->stepExecution->getJobExecution()->getJobInstance()->getRawConfiguration();
        $configuration['export']['firstLineIsHeader'] = false;
        $this->stepExecution->getJobExecution()->getJobInstance()->setRawConfiguration($configuration);

        parent::initializeFromContext($context);
    }

    /**
     * @return \Iterator
     */
    protected function createIterator()
    {
        if ($this->priceListId) {
            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');

            /** @var PriceListToProductRepository $repository */
            $repository = $em->getRepository('OroB2BPricingBundle:PriceListToProduct');
            
            /** @var PriceList $priceList */
            $priceList = $em->getReference('OroB2BPricingBundle:PriceList', $this->priceListId);

            return new AdditionalProductPricesIterator(
                $repository->getProductsWithoutPrices($priceList),
                $priceList,
                $this->currencyManager->getAvailableCurrencies()
            );
        } else {
            return new \ArrayIterator();
        }
    }
}
