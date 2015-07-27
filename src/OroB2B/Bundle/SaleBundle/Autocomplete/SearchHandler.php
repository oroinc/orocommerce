<?php

namespace OroB2B\Bundle\SaleBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;

class SearchHandler extends BaseSearchHandler
{
    const DELIMITER = ';';

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (false === strpos($search, static::DELIMITER)) {
            return [];
        }
        list($accountId, $searchTerm) = explode(static::DELIMITER, $search, 2);
        $entityIds = $this->searchIds($searchTerm, $firstResult, $maxResults);
        if (!count($entityIds)) {
            return [];
        }
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder
            ->where($queryBuilder->expr()->in('e.' . $this->idFieldName, $entityIds))
            ->addOrderBy('e.email', 'ASC')
        ;

        if ($accountId) {
            $queryBuilder
                ->andWhere('e.customer = :account')
                ->setParameter('account', $accountId)
            ;
        }

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }
}
