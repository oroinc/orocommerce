<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;

/**
 * Normalizes {@see ResolvedContentNode} for usage in cache.
 */
class ResolvedContentNodeNormalizer
{
    private const array NODE_NAME_MAP = [
        'id' => 'i',
        'identifier' => 'a',
        'priority' => 'p',
        'rewriteVariantTitle' => 'r',
        'titles' => 't',
        'contentVariant' => 'c',
        'childNodes' => 'n'
    ];
    private const array CONTENT_VARIANT_NAME_MAP = [
        'id' => 'i',
        'type' => 't',
        'default' => 'd',
        'overrideVariantConfiguration' => 'o',
        'doNotRenderTitle' => 'n',
        'cms_page' => 'p',
        'slugs' => [
            '.' => 's',
            'url' => 'u',
            'localization' => 'l',
            'fallback' => 'f'
        ],
        'description' => 'ds',
        'product_page_product' => 'pp',
        'product_collection_segment' => 'pc',
        'exclude_subcategories' => 'e',
        'category_page_category' => 'c',
        'media_kit' => 'm',
        'systemPageRoute' => 'r'
    ];
    private const array ENTITY_REF_NAME_MAP = [
        'class' => 'o',
        'id' => 'i'
    ];

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer,
        private readonly ResolvedContentNodeFactory $resolvedContentNodeFactory
    ) {
    }

    public function normalize(ResolvedContentNode $resolvedNode, array $context = []): array
    {
        return $this->normalizeNode($resolvedNode);
    }

    /**
     * @param array $data
     * @param array $context Available context options:
     *  [
     *      'tree_depth' => int, // Restricts the maximum tree depth. -1 stands for unlimited.
     *  ]
     *
     * @return ResolvedContentNode|null
     */
    public function denormalize(array $data, array $context = []): ?ResolvedContentNode
    {
        return $this->denormalizeNode($data, $context['tree_depth'] ?? -1);
    }

    private function normalizeNode(ResolvedContentNode $resolvedNode): array
    {
        return [
            self::NODE_NAME_MAP['id'] => $resolvedNode->getId(),
            self::NODE_NAME_MAP['identifier'] => $resolvedNode->getIdentifier(),
            self::NODE_NAME_MAP['priority'] => $resolvedNode->getPriority(),
            self::NODE_NAME_MAP['rewriteVariantTitle'] => $resolvedNode->isRewriteVariantTitle(),
            self::NODE_NAME_MAP['titles'] => $this->normalizeTitles($resolvedNode->getTitles()),
            self::NODE_NAME_MAP['contentVariant'] => $this->normalizeContentVariant(
                $resolvedNode->getResolvedContentVariant()
            ),
            self::NODE_NAME_MAP['childNodes'] => $this->normalizeArray($resolvedNode->getChildNodes())
        ];
    }

    private function denormalizeNode(array $data, int $treeDepth): ResolvedContentNode
    {
        $data = $this->denormalizeArrayItem($data, self::NODE_NAME_MAP);

        if (!isset($data['id'])) {
            throw new InvalidArgumentException(
                'Element "id" is required for the denormalization of ResolvedContentNode.'
            );
        }
        if (!isset($data['identifier'])) {
            throw new InvalidArgumentException(
                'Element "identifier" is required for the denormalization of ResolvedContentNode.'
            );
        }

        if (!empty($data['contentVariant'])) {
            $data['contentVariant'] = $this->denormalizeArrayItem(
                $data['contentVariant'],
                self::CONTENT_VARIANT_NAME_MAP
            );
        }

        $resolvedNode = $this->resolvedContentNodeFactory->createFromArray($data);
        if (0 === $treeDepth) {
            return $resolvedNode;
        }

        if (!empty($data['childNodes'])) {
            $treeDepth--;
            foreach ($data['childNodes'] as $childNode) {
                $resolvedNode->addChildNode($this->denormalizeNode($childNode, $treeDepth));
            }
        }

        return $resolvedNode;
    }

    private function normalizeTitles(Collection $titles): array
    {
        $result = [];
        foreach ($titles as $title) {
            $result[] = $this->localizedFallbackValueNormalizer->normalize($title);
        }

        return $result;
    }

    private function normalizeContentVariant(ResolvedContentVariant $contentVariant): array
    {
        $result = $this->normalizeArray($contentVariant->getData(), self::CONTENT_VARIANT_NAME_MAP, true);
        $slugNameMap = self::CONTENT_VARIANT_NAME_MAP['slugs'];
        $result[$slugNameMap['.']] = $this->normalizeSlugs($contentVariant->getLocalizedUrls(), $slugNameMap);

        return $result;
    }

    private function normalizeSlugs(Collection $slugs, array $slugNameMap): array
    {
        $result = [];
        foreach ($slugs as $slug) {
            $result[] = $this->normalizeSlug($slug, $slugNameMap);
        }

        return $result;
    }

    private function normalizeSlug(LocalizedFallbackValue $slug, array $slugNameMap): array
    {
        $result = [
            $slugNameMap['url'] => $slug->getString() ?: $slug->getText()
        ];
        $localization = $slug->getLocalization();
        if (null !== $localization) {
            $result[$slugNameMap['localization']] = $this->normalizeEntityReference($localization);
        }
        $fallback = $slug->getFallback();
        if (null !== $fallback) {
            $result[$slugNameMap['fallback']] = $fallback;
        }

        return $result;
    }

    private function normalizeEntityReference($object): ?array
    {
        if (null === $object) {
            return null;
        }

        return [
            self::ENTITY_REF_NAME_MAP['class'] => $this->doctrineHelper->getEntityClass($object),
            self::ENTITY_REF_NAME_MAP['id'] => $this->doctrineHelper->getSingleEntityIdentifier($object)
        ];
    }

    private function isEntityReference(array $val): bool
    {
        return
            \count($val) === 2
            && isset($val[self::ENTITY_REF_NAME_MAP['class']], $val[self::ENTITY_REF_NAME_MAP['id']]);
    }

    private function normalizeArray(iterable $traversable, array $nameMap = [], bool $skipNulls = false): array
    {
        $result = [];
        foreach ($traversable as $name => $value) {
            if ($skipNulls && null === $value) {
                continue;
            }

            if (\is_object($value)) {
                $value = $this->normalizeObject($value);
            } elseif (\is_array($value)) {
                $value = $this->normalizeArray($value, $nameMap[$name] ?? []);
            }

            $result[$nameMap[$name]['.'] ?? $nameMap[$name] ?? $name] = $value;
        }

        return $result;
    }

    private function denormalizeArrayItem(array $data, array $nameMap = []): array
    {
        foreach ($nameMap as $name => $key) {
            $valNameMap = [];
            if (\is_array($key)) {
                $valNameMap = $key;
                $key = $key['.'];
            }
            if (\array_key_exists($key, $data)) {
                $val = $data[$key];
                unset($data[$key]);
                $data[$name] = $this->denormalizeArrayValue($val, $valNameMap);
            }
        }
        foreach ($data as $name => $val) {
            if (!isset($nameMap[$name])) {
                $data[$name] = $this->denormalizeArrayValue($val);
            }
        }

        return $data;
    }

    private function denormalizeArrayValue(mixed $val, array $valNameMap = []): mixed
    {
        if (\is_array($val)) {
            if ($valNameMap) {
                return array_is_list($val)
                    ? $this->denormalizeArrayList($val, $valNameMap)
                    : $this->denormalizeArrayItem($val, $valNameMap);
            }
            if ($this->isEntityReference($val)) {
                return [
                    'class' => $val[self::ENTITY_REF_NAME_MAP['class']],
                    'id' => $val[self::ENTITY_REF_NAME_MAP['id']]
                ];
            }
        }

        return $val;
    }

    private function denormalizeArrayList(array $list, array $nameMap = []): array
    {
        $result = [];
        foreach ($list as $item) {
            $result[] = $this->denormalizeArrayItem($item, $nameMap);
        }

        return $result;
    }

    private function normalizeObject($value): ?array
    {
        if ($value instanceof LocalizedFallbackValue) {
            return $this->localizedFallbackValueNormalizer->normalize($value);
        }
        if ($value instanceof ResolvedContentNode) {
            return $this->normalizeNode($value);
        }
        if ($value instanceof \Traversable) {
            return $this->normalizeArray($value);
        }
        if ($this->doctrineHelper->isManageableEntity($value)) {
            return $this->normalizeEntityReference($value);
        }

        return null;
    }
}
