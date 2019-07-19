<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Keep slug URLs unique per entity by adding suffix on duplicates.
 */
class UniqueSlugResolver
{
    const INCREMENTED_SLUG_PATTERN = '/^(.*)-\d+$/';
    const SLUG_INCREMENT_PATTERN = '/^%s-(\d+)$/';
    const SLUG_INCREMENT_DATABASE_PATTERN = '%s-%%';

    /**
     * @var SlugRepository
     */
    protected $repository;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * Store URLs processed in the current batch to increment suffixes for entities withing same transaction.
     *
     * @var array
     */
    private $processedUrls = [];

    /**
     * @param SlugRepository $repository
     * @param AclHelper $aclHelper
     */
    public function __construct(SlugRepository $repository, AclHelper $aclHelper)
    {
        $this->repository = $repository;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param SlugUrl $slugUrl
     * @param SluggableInterface $entity
     * @return string
     */
    public function resolve(SlugUrl $slugUrl, SluggableInterface $entity)
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

    public function onFlush()
    {
        $this->processedUrls = [];
    }

    /**
     * @param string $baseSlug
     * @param SluggableInterface $entity
     * @return string
     */
    protected function getIncrementedSlug($baseSlug, SluggableInterface $entity)
    {
        $index = 0;
        $possibleMatches = $this->getPreMatchedIncrementSlug($baseSlug, $entity);

        foreach ($possibleMatches as $incrementedSlug) {
            if (preg_match($this->buildSlugIncrementPattern($baseSlug), $incrementedSlug, $matches)) {
                $index = max($index, $matches[1]);
            }
        }

        $incrementedSlug = sprintf('%s-%d', $baseSlug, ++$index);

        return $incrementedSlug;
    }

    /**
     * @param string $slug
     * @param SluggableInterface $entity
     * @return string
     */
    protected function getBaseSlug($slug, SluggableInterface $entity)
    {
        if (preg_match(self::INCREMENTED_SLUG_PATTERN, $slug, $matches)) {
            $baseSlug = $matches[1];

            $qb = $this->repository->getOneDirectUrlBySlugQueryBuilder($baseSlug, $entity);
            if ($this->aclHelper->apply($qb)->getOneOrNullResult()) {
                return $baseSlug;
            }
        }

        return $slug;
    }

    /**
     * @param string $slug
     * @param SluggableInterface $entity
     * @return array
     */
    protected function getPreMatchedIncrementSlug($slug, SluggableInterface $entity)
    {
        return array_merge(
            $this->findProcessedUrls($slug, $entity),
            $this->findStoredUrls($slug, $entity)
        );
    }

    /**
     * @param string $slug
     * @return string
     */
    protected function buildSlugIncrementPattern($slug)
    {
        return sprintf(self::SLUG_INCREMENT_PATTERN, preg_quote($slug, '/'));
    }

    /**
     * @param string $slug
     * @param SluggableInterface $entity
     * @return bool
     */
    private function hasExistingSlug(string $slug, SluggableInterface $entity): bool
    {
        if ($this->hasUrlDuplicateWithinBatch($slug, $entity)) {
            return true;
        }

        $qb = $this->repository->getOneDirectUrlBySlugQueryBuilder($slug, $entity);

        return (bool) $this->aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param $slug
     * @param SluggableInterface $entity
     * @return array|string[]
     */
    private function findStoredUrls(string $slug, SluggableInterface $entity)
    {
        return $this->repository->findAllDirectUrlsByPattern(
            sprintf(self::SLUG_INCREMENT_DATABASE_PATTERN, $slug),
            $entity
        );
    }

    /**
     * @param string $slug
     * @param SluggableInterface $entity
     * @return array
     */
    private function findProcessedUrls(string $slug, SluggableInterface $entity): array
    {
        $foundUrls = [];
        $currentEntityId = $this->getEntityIdentifier($entity);
        foreach ($this->processedUrls as $url => $entityId) {
            if ($entityId !== $currentEntityId && strpos($url, $slug . '-') === 0) {
                $foundUrls[] = $url;
            }
        }

        return $foundUrls;
    }

    /**
     * @param SluggableInterface $entity
     * @return string
     */
    private function getEntityIdentifier(SluggableInterface $entity): string
    {
        return ClassUtils::getClass($entity) . ':' . $entity->getId();
    }

    /**
     * @param string $slug
     * @param SluggableInterface $entity
     * @return bool
     */
    private function hasUrlDuplicateWithinBatch(string $slug, SluggableInterface $entity): bool
    {
        return !empty($this->processedUrls[$slug])
            && $this->processedUrls[$slug] !== $this->getEntityIdentifier($entity);
    }
}
