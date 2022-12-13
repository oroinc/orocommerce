<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * Creates {@see ResolvedContentVariant} from array.
 */
class ResolvedContentVariantFactory
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param array $contentVariantData Content node data coming from either cache or from the array hydrator.
     *  [
     *      'slugs' => [
     *          [
     *              'url' => ?string,
     *              'localization' => ?array [
     *                  'id' => int,
     *              ],
     *          ],
     *          // ...
     *      ],
     *      // ... fields and to-one associations of {@see ContentVariant}
     *  ]
     *
     * @return ResolvedContentVariant
     */
    public function createFromArray(array $contentVariantData): ResolvedContentVariant
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(ContentVariant::class);
        $metadata = $entityManager->getClassMetadata(ContentVariant::class);

        $resolvedVariant = new ResolvedContentVariant();
        foreach ($metadata->getFieldNames() as $fieldName) {
            if (isset($contentVariantData[$fieldName])) {
                $resolvedVariant->{$fieldName} = $contentVariantData[$fieldName];
            }
        }

        $this->addSlugs($entityManager, $contentVariantData['slugs'] ?? [], $resolvedVariant);

        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($metadata->isCollectionValuedAssociation($associationName)) {
                // To-Many associations are not supported.
                continue;
            }

            // Skips ContentNode associations.
            if ($metadata->getAssociationTargetClass($associationName) === ContentNode::class) {
                continue;
            }

            $associatedValue = $contentVariantData[$associationName] ?? null;
            if ($associatedValue !== null) {
                $resolvedVariant->{$associationName} = $entityManager->getReference(
                    $metadata->getAssociationTargetClass($associationName),
                    $associatedValue['id']
                );
            }
        }

        return $resolvedVariant;
    }

    /**
     * @param array<array> $slugs
     *  [
     *      [
     *          'url' => ?string,
     *          'localization' => ?array [
     *              'id' => int,
     *          ],
     *      ],
     *      // ...
     *  ]
     * @param ResolvedContentVariant $resolvedVariant
     */
    private function addSlugs(
        EntityManager $entityManager,
        array $slugs,
        ResolvedContentVariant $resolvedVariant
    ): void {
        foreach ($slugs as $slugData) {
            $localizedUrl = (new LocalizedFallbackValue())
                ->setString($slugData['url'] ?? '');

            if (isset($slugData['localization']['id'])) {
                $localizedUrl->setLocalization(
                    $this->getLocalization($entityManager, $slugData['localization']['id'])
                );
            }

            $resolvedVariant->addLocalizedUrl($localizedUrl);
        }
    }

    private function getLocalization(EntityManager $entityManager, int $localizationId): Localization
    {
        return $entityManager->getReference(Localization::class, $localizationId);
    }
}
