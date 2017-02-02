<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;

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
     * @var SlugRepository
     */
    protected $repository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param SlugUrl $slugUrl
     * @param SluggableInterface $entity
     * @return string
     */
    public function resolve(SlugUrl $slugUrl, SluggableInterface $entity)
    {
        $slug = $slugUrl->getUrl();

        if ($this->getRepository()->findOneBySlugWithoutScopes($slug, $entity)) {
            $baseSlug = $this->getBaseSlug($slug, $entity);

            $resolvedSlug = $this->getIncrementedSlug($baseSlug, $entity);
        } else {
            $resolvedSlug = $slug;
        }

        return $resolvedSlug;
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
     * @return SlugRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->registry->getManagerForClass(Slug::class)->getRepository(Slug::class);
        }

        return $this->repository;
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

            if ($this->getRepository()->findOneBySlugWithoutScopes($baseSlug, $entity)) {
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
        return $this->getRepository()
            ->findAllByPatternWithoutScopes(sprintf(self::SLUG_INCREMENT_DATABASE_PATTERN, $slug), $entity);
    }

    /**
     * @param string $slug
     * @return string
     */
    protected function buildSlugIncrementPattern($slug)
    {
        return sprintf(self::SLUG_INCREMENT_PATTERN, preg_quote($slug, '/'));
    }
}
