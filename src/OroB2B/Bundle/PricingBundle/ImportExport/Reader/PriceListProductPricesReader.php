<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class PriceListProductPricesReader extends EntityReader
{
    /**
     * @var int
     */
    protected $priceListId;

    /**
     * {@inheritdoc}
     */
    public function setSourceQueryBuilder(QueryBuilder $queryBuilder)
    {
        if ($this->priceListId) {
            $aliases = $queryBuilder->getRootAliases();
            $rootAlias = reset($aliases);
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(sprintf('IDENTITY(%s.priceList)', $rootAlias), ':priceList')
                )
                ->setParameter('priceList', $this->priceListId);
        }

        parent::setSourceQueryBuilder($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->priceListId = (int)$context->getOption('price_list_id');

        parent::initializeFromContext($context);
    }
}
