<?php

namespace OroB2B\Bundle\CustomerBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;

class CustomerAccountUserSearchHandler extends BaseSearchHandler
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
            ->addOrderBy($queryBuilder->expr()->asc('e.email'))
        ;

        if ($accountId) {
            $queryBuilder
                ->andWhere('e.account = :account')
                ->setParameter('account', $accountId)
            ;
        }

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }
}
