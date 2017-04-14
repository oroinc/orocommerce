<?php

namespace Oro\Bundle\TaxBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;

class SearchHandler extends BaseSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');

        if ($search) {
            foreach ($this->getProperties() as $fieldName) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->lower('e.' . $fieldName),
                        $queryBuilder->expr()->lower($queryBuilder->expr()->literal('%' . $search . '%'))
                    )
                );
            }
        }

        $queryBuilder->setMaxResults($maxResults);
        $queryBuilder->setFirstResult($firstResult);

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getArrayResult();
    }
}
