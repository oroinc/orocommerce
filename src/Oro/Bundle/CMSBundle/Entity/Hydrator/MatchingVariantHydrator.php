<?php

namespace Oro\Bundle\CMSBundle\Entity\Hydrator;

use Doctrine\DBAL\ParameterType;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Entity\Hydrator\AbstractMatchingEntityHydrator;

/**
 * Custom hydrator that increases performance when getting the matching text content variant.
 * Requires matchedScopeId to be selected
 */
class MatchingVariantHydrator extends AbstractMatchingEntityHydrator
{
    public const NAME = 'oro.cms.entity.hydrator.matching_variant';

    #[\Override]
    protected function getRootEntityAlias(): string
    {
        return 'variant';
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return TextContentVariant::class;
    }

    #[\Override]
    protected function hasScopes($variantId): bool
    {
        $hasScopeQuery = 'SELECT 1 FROM oro_cms_txt_cont_variant_scope WHERE variant_id = :id LIMIT 1';
        $hasScopes = $this->_em->getConnection()
            ->executeQuery($hasScopeQuery, ['id' => $variantId], ['id' => ParameterType::INTEGER])
            ->fetchOne();

        return (bool)$hasScopes;
    }
}
