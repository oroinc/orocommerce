<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
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

            // TODO: move to repository method, cover with functional test
            $qb = $em->createQueryBuilder();
            $qb->select('p')
                ->from('OroB2BProductBundle:Product', 'p')
                ->join(
                    'OroB2BPricingBundle:PriceListToProduct',
                    'plp',
                    Join::WITH,
                    $qb->expr()->eq('plp.product', 'p')
                )
                ->leftJoin(
                    'OroB2BPricingBundle:ProductPrice',
                    'pp',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('pp.product', 'plp.product'),
                        $qb->expr()->eq('pp.priceList', 'plp.priceList')
                    )
                )
                ->where($qb->expr()->isNull('pp.id'))
                ->andWhere($qb->expr()->eq('plp.priceList', ':priceList'))
                ->setParameter('priceList', $this->priceListId);

            /** @var PriceList $priceList */
            $priceList = $em->getReference('OroB2BPricingBundle:PriceList', $this->priceListId);

            return new AdditionalProductPricesIterator(
                $qb,
                $priceList,
                $this->currencyManager->getAvailableCurrencies()
            );
        } else {
            return new \ArrayIterator();
        }
    }
}
