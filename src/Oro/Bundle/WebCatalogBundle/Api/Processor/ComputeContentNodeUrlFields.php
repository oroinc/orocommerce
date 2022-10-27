<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\RedirectBundle\Api\Processor\ComputeUrlFields;

/**
 * Computes values of "url" and "urls" fields for ContentNode entity.
 */
class ComputeContentNodeUrlFields extends ComputeUrlFields
{
    /**
     * {@inheritdoc}
     */
    protected function getQueryForLoadUrls(
        string $ownerEntityClass,
        string $ownerEntityIdFieldName,
        array $ownerIds,
        array $localizationIds
    ): QueryBuilder {
        return $this->doctrineHelper
            ->createQueryBuilder($ownerEntityClass, 'owner')
            ->select(sprintf(
                'lfv.text AS url, IDENTITY(lfv.localization) AS localizationId, owner.%s AS ownerId',
                $ownerEntityIdFieldName
            ))
            ->innerJoin('owner.localizedUrls', 'lfv')
            ->where('owner IN (:ownerIds) AND (lfv.localization IN (:localizationIds) OR lfv.localization IS NULL)')
            ->setParameter('ownerIds', $ownerIds)
            ->setParameter('localizationIds', $localizationIds);
    }
}
