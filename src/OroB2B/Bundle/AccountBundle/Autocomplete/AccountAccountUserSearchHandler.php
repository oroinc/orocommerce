<?php

namespace OroB2B\Bundle\AccountBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;

class AccountAccountUserSearchHandler extends BaseSearchHandler
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
        list($searchTerm, $accountId) = explode(static::DELIMITER, $search, 2);
        $entityIds = $this->searchIds($searchTerm, $firstResult, $maxResults);
        if (!count($entityIds)) {
            return [];
        }
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder
            ->where($queryBuilder->expr()->in('e.' . $this->idFieldName, $entityIds))
            ->addOrderBy($queryBuilder->expr()->asc('e.email'));

        if ($accountId) {
            $queryBuilder
                ->andWhere('e.account = :account')
                ->setParameter('account', $accountId);
        }

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function findById($query)
    {
        $parts = explode(self::DELIMITER, $query);
        $id = $parts[0];
        $accountId = !empty($parts[1]) ? $parts[1] : false;

        $criteria = [$this->idFieldName => $id];
        if (false !== $accountId) {
            $criteria['account'] = $accountId;
        }

        return [$this->entityRepository->findOneBy($criteria, null)];
    }
}
