<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Event\RestrictSlugIncrementEvent;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Keep slug URLs unique per entity by adding suffix on duplicates.
 */
class UniqueSlugResolver
{
    const INCREMENTED_SLUG_PATTERN = '/^(.*)-\d+$/';
    const SLUG_INCREMENT_PATTERN = '/^%s-(\d+)$/';
    const SLUG_INCREMENT_DATABASE_PATTERN = '%s-%%';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Store URLs processed in the current batch to increment suffixes for entities withing same transaction.
     *
     * @var array
     */
    private $processedUrls = [];

    public function __construct(
        ManagerRegistry $registry,
        AclHelper $aclHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
        $this->eventDispatcher = $eventDispatcher;
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

            $qb = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder($baseSlug, $entity);
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

    private function hasExistingSlug(string $slug, SluggableInterface $entity): bool
    {
        if ($this->hasUrlDuplicateWithinBatch($slug, $entity)) {
            return true;
        }

        $qb = $this->getSlugRepository()->getOneDirectUrlBySlugQueryBuilder($slug, $entity);
        $qb = $this->getRestrictedOneDirectUrlBySlugQueryBuilder($qb, $entity);

        return (bool) $this->aclHelper->apply($qb)->getOneOrNullResult();
    }

    /**
     * @param $slug
     * @param SluggableInterface $entity
     * @return array|string[]
     */
    private function findStoredUrls(string $slug, SluggableInterface $entity)
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
            if ($entityId !== $currentEntityId && strpos($url, $slug . '-') === 0) {
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

    private function getRestrictedOneDirectUrlBySlugQueryBuilder(
        QueryBuilder $qb,
        SluggableInterface $entity
    ): QueryBuilder {
        $restrictSlugIncrementEvent = new RestrictSlugIncrementEvent($qb, $entity);
        $this->eventDispatcher->dispatch($restrictSlugIncrementEvent, RestrictSlugIncrementEvent::NAME);

        return $restrictSlugIncrementEvent->getQueryBuilder();
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->registry->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);
    }
}
