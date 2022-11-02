<?php

namespace Oro\Bundle\TaxBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * The autocomplete handler to search product TAX codes.
 */
class SearchHandler extends BaseSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('e');

        if ($search) {
            $idx = 0;
            foreach ($this->getProperties() as $fieldName) {
                QueryBuilderUtil::checkIdentifier($fieldName);
                $paramName = 'search' . $idx;
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->lower('e.' . $fieldName),
                        $queryBuilder->expr()->lower(':' . $paramName)
                    )
                );
                $queryBuilder->setParameter($paramName, '%' . $search . '%');
                $idx++;
            }
        }

        $queryBuilder->setMaxResults($maxResults);
        $queryBuilder->setFirstResult($firstResult);

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getArrayResult();
    }
}
