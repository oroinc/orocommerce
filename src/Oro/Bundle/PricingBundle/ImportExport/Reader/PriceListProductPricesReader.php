<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class PriceListProductPricesReader extends EntityReader
{
    /**
     * @var int
     */
    protected $priceListId;

    /**
     * {@inheritdoc}
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null)
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization);

        if ($this->priceListId) {
            $aliases = $qb->getRootAliases();
            $rootAlias = reset($aliases);
            $qb
                ->andWhere(
                    $qb->expr()->eq(sprintf('IDENTITY(%s.priceList)', $rootAlias), ':priceList')
                )
                ->setParameter('priceList', $this->priceListId);
        }

        return $qb;
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
