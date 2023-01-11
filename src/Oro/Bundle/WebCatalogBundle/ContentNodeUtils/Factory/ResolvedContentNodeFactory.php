<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;

/**
 * Creates {@see ResolvedContentNode} from array.
 */
class ResolvedContentNodeFactory
{
    private ResolvedContentNodeIdentifierGenerator $resolvedContentNodeIdentifierGenerator;

    private ResolvedContentVariantFactory $resolvedContentVariantFactory;

    private LocalizedFallbackValueCollectionNormalizer $localizedFallbackValueCollectionNormalizer;

    public function __construct(
        ResolvedContentNodeIdentifierGenerator $resolvedContentNodeIdentifierGenerator,
        ResolvedContentVariantFactory $resolvedContentVariantFactory,
        LocalizedFallbackValueCollectionNormalizer $localizedFallbackValueCollectionNormalizer
    ) {
        $this->resolvedContentNodeIdentifierGenerator = $resolvedContentNodeIdentifierGenerator;
        $this->resolvedContentVariantFactory = $resolvedContentVariantFactory;
        $this->localizedFallbackValueCollectionNormalizer = $localizedFallbackValueCollectionNormalizer;
    }

    /**
     * @param array $contentNodeData Content node data coming from either cache or from the array hydrator.
     *  [
     *      'id' => int,
     *      'identifier' => string,
     *      'localizedUrls' => [ // Not required if 'identifier' is present.
     *          [
     *              'text' => ?string,
     *              'localization' => ?array [
     *                  'id' => int,
     *              ],
     *          ],
     *          // ...
     *      ],
     *      'priority' => int,
     *      'left' => int, // Not required if 'priority' is present.
     *      'titles' => [
     *          [
     *              'string' => ?string,
     *              'fallback' => ?string,
     *              'localization' => ?array [
     *                  'id' => int
     *              ],
     *          ],
     *          // ...
     *      ],
     *      'contentVariant' => ResolvedContentNode|array, // Array as required by {@see ResolvedContentVariantFactory},
     *      'rewriteVariantTitle' => bool,
     *  ]
     *
     * @return ResolvedContentNode
     */
    public function createFromArray(array $contentNodeData): ResolvedContentNode
    {
        if (!isset($contentNodeData['id'])) {
            throw new InvalidArgumentException('Element "id" is required and expected to be of type int');
        }

        $resolvedContentVariant = $contentNodeData['contentVariant'] ?? null;
        if (!$resolvedContentVariant instanceof ResolvedContentVariant) {
            if (!is_array($resolvedContentVariant)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Element "contentVariant" is required and expected to be array or %s',
                        ResolvedContentNode::class
                    )
                );
            }

            $resolvedContentVariant = $this->resolvedContentVariantFactory
                ->createFromArray($contentNodeData['contentVariant']);
        }

        $identifier = $contentNodeData['identifier'] ?? null;
        if ($identifier === null) {
            if (!isset($contentNodeData['localizedUrls'])) {
                throw new InvalidArgumentException(
                    'Either "identifier" or "localizedUrls" element is expected to be present'
                );
            }

            if (!is_array($contentNodeData['localizedUrls'])) {
                throw new InvalidArgumentException('Element "localizedUrls" is expected to be array');
            }

            $localizedUrl = $this->getUrlForIdentifier($contentNodeData['localizedUrls']);
            $identifier = $this->resolvedContentNodeIdentifierGenerator->getIdentifierByUrl($localizedUrl);
        }

        return new ResolvedContentNode(
            $contentNodeData['id'],
            (string)$identifier,
            $contentNodeData['priority'] ?? $contentNodeData['left'] ?? 0,
            $this->createTitles($contentNodeData['titles'] ?? []),
            $resolvedContentVariant,
            (bool)($contentNodeData['rewriteVariantTitle'] ?? true),
        );
    }

    /**
     * @param array $localizedUrls
     *  [
     *      [
     *          'text' => ?string,
     *          'localization' => ?array [
     *              'id' => int,
     *          ],
     *      ],
     *      // ...
     *  ]
     * @return string
     */
    private function getUrlForIdentifier(array $localizedUrls): string
    {
        foreach ($localizedUrls as $localizedUrl) {
            if (!isset($localizedUrl['localization']['id'])) {
                return $localizedUrl['text'] ?? '';
            }
        }

        return '';
    }

    /**
     * @param array $titles
     *  [
     *      [
     *          'string' => ?string,
     *          'fallback' => ?string,
     *          'localization' => ?array [
     *              'id' => int
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     *
     * @return Collection<LocalizedFallbackValue>
     */
    private function createTitles(array $titles): Collection
    {
        if (!$titles) {
            return new ArrayCollection();
        }

        return $this->localizedFallbackValueCollectionNormalizer->denormalize($titles, LocalizedFallbackValue::class);
    }
}
