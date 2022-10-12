<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;

/**
 * Keep slug URLs unique per entity by adding suffix on duplicates.
 */
class UniqueSlugResolver implements UniqueSlugResolverInterface
{
    public const INCREMENTED_SLUG_PATTERN = '/^(.*)-\d+$/';
    public const SLUG_INCREMENT_PATTERN = '/^%s-(\d+)$/';
    public const SLUG_INCREMENT_DATABASE_PATTERN = '%s-%%';

    private ManagerRegistry $doctrine;

    /**
     * Store URLs processed in the current batch to increment suffixes for entities withing same transaction.
     */
    private array $processedUrls = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function resolve(SlugUrl $slugUrl, SluggableInterface $entity): string
    {
        $slug = $slugUrl->getUrl();

        if ($this->hasExistingSlug($slug, $entity)) {
            $baseSlug = $this->getBaseSlug($slug, $entity);

            $resolvedSlug = $this->getIncrementedSlug($baseSlug, $entity);
        } else {
            $resolvedSlug = $slug;
        }
        $this->processedUrls[$resolvedSlug] = $this->getEntityIdentifier($entity);

        return $resolvedSlug;
    }

    public function onFlush(): void
    {
        $this->processedUrls = [];
    }

    private function getIncrementedSlug(string $baseSlug, SluggableInterface $entity): string
    {
        $index = 0;
        $possibleMatches = $this->getPreMatchedIncrementSlug($baseSlug, $entity);
        foreach ($possibleMatches as $incrementedSlug) {
            if (preg_match($this->buildSlugIncrementPattern($baseSlug), $incrementedSlug, $matches)) {
                $index = max($index, $matches[1]);
            }
        }

        return sprintf('%s-%d', $baseSlug, ++$index);
    }

    private function getBaseSlug(string $slug, SluggableInterface $entity): string
    {
        if (preg_match(self::INCREMENTED_SLUG_PATTERN, $slug, $matches)) {
            $baseSlug = $matches[1];

            $qb = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder($baseSlug, $entity);
            if ($qb->getQuery()->getOneOrNullResult()) {
                return $baseSlug;
            }
        }

        return $slug;
    }

    private function getPreMatchedIncrementSlug(string $slug, SluggableInterface $entity): array
    {
        return array_merge(
            $this->findProcessedUrls($slug, $entity),
            $this->findStoredUrls($slug, $entity)
        );
    }

    private function buildSlugIncrementPattern(string $slug): string
    {
        return sprintf(self::SLUG_INCREMENT_PATTERN, preg_quote($slug, '/'));
    }

    private function hasExistingSlug(string $slug, SluggableInterface $entity): bool
    {
        if ($this->hasUrlDuplicateWithinBatch($slug, $entity)) {
            return true;
        }

        $qb = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder($slug, $entity);

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    private function findStoredUrls(string $slug, SluggableInterface $entity): array
    {
        return $this->getSlugRepository()->findAllDirectUrlsByPattern(
            sprintf(self::SLUG_INCREMENT_DATABASE_PATTERN, $slug),
            $entity
        );
    }

    private function findProcessedUrls(string $slug, SluggableInterface $entity): array
    {
        $foundUrls = [];
        $currentEntityId = $this->getEntityIdentifier($entity);
        foreach ($this->processedUrls as $url => $entityId) {
            if ($entityId !== $currentEntityId && str_starts_with($url, $slug . '-')) {
                $foundUrls[] = $url;
            }
        }

        return $foundUrls;
    }

    private function getEntityIdentifier(SluggableInterface $entity): string
    {
        return ClassUtils::getClass($entity) . ':' . $entity->getId();
    }

    private function hasUrlDuplicateWithinBatch(string $slug, SluggableInterface $entity): bool
    {
        return !empty($this->processedUrls[$slug])
            && $this->processedUrls[$slug] !== $this->getEntityIdentifier($entity);
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getRepository(Slug::class);
    }
}
