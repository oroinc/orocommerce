<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentVariantFactory;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * Creates {@see ResolvedContentVariant} objects for specified {@see ContentVariant} IDs.
 */
class ResolvedContentVariantsLoader
{
    private ManagerRegistry $managerRegistry;

    private ResolvedContentVariantFactory $resolvedContentVariantFactory;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ResolvedContentVariantFactory $resolvedContentVariantFactory
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->resolvedContentVariantFactory = $resolvedContentVariantFactory;
    }

    /**
     * @param int[] $contentVariantIds
     * @return array<int,array<int,ResolvedContentVariant>>
     *  [
     *      int $contentNodeId => [
     *          int $contentVariantId => ResolvedContentVariant,
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function loadResolvedContentVariants(array $contentVariantIds): array
    {
        if (!$contentVariantIds) {
            return [];
        }

        $contentVariantsData = $this->managerRegistry
            ->getRepository(ContentVariant::class)
            ->getContentVariantsData($contentVariantIds);

        $resolvedVariants = [];
        foreach ($contentVariantsData as $data) {
            $resolvedVariants[$data['node']['id']][$data['id']] = $this->resolvedContentVariantFactory
                ->createFromArray($data);
        }

        return $resolvedVariants;
    }
}
