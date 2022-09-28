<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;

/**
 * Doctrine repository for ContentTemplate entity.
 */
class ContentTemplateRepository extends ServiceEntityRepository
{
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $registry, string $entityClass, AclHelper $aclHelper)
    {
        parent::__construct($registry, $entityClass);

        $this->aclHelper = $aclHelper;
    }

    /**
     * Finds Content Templates assigned with the specified $tags or all Content Templates if no $tags provided.
     *
     * @param Tag[]|int[] $tags
     *
     * @return list<array{template: ContentTemplate, tags: string[]}>
     *  [
     *      [
     *          'template' => ContentTemplate,
     *          'tags' => string[], // List of tags assigned to a Content Template
     *      ],
     *     // ...
     *  ]
     */
    public function findContentTemplatesByTags(array $tags = []): array
    {
        $queryBuilder = $this
            ->createQueryBuilder('ct')
            ->select('PARTIAL ct.{id, name} as template')
            ->addSelect('previewImage')
            ->addSelect('JSON_AGG(IDENTITY(tg.tag)) as tags')
            ->leftJoin(Tagging::class, 'tg', Join::WITH, 'tg.recordId = ct.id AND tg.entityName = :entityClassName')
            ->leftJoin('tg.tag', 't')
            ->leftJoin('ct.previewImage', 'previewImage')
            ->where('ct.enabled = :enabled')
            ->groupBy('ct.id')
            ->addGroupBy('previewImage.id')
            ->setParameter('enabled', true)
            ->setParameter('entityClassName', ContentTemplate::class)
            ->orderBy('ct.createdAt', 'ASC');

        if (!empty($tags)) {
            $queryBuilder
                ->andWhere('IDENTITY(tg.tag) IN (:tags)')
                ->setParameter('tags', $tags);
        }

        $query = $queryBuilder->getQuery();

        /** @var list<array{template: ContentTemplate, tags: int[]}> $contentTemplatesData */
        $contentTemplatesData = $this->aclHelper->apply($query)->getResult();
        foreach ($contentTemplatesData as $index => $row) {
            $contentTemplatesData[$index]['tags'] = array_filter(json_decode($row['tags'], true));
        }

        $tagsQueryBuilder = $this->getEntityManager()->createQueryBuilder();
        $tagsQuery = $tagsQueryBuilder
            ->select('t.name', 't.id')
            ->from(Tag::class, 't', 't.id')
            ->where($tagsQueryBuilder->expr()->in('t.id', ':ids'))
            ->setParameter(
                'ids',
                array_values(array_unique(array_merge(...array_column($contentTemplatesData, 'tags')))),
                Connection::PARAM_INT_ARRAY
            )
            ->getQuery();

        $tags = array_column($this->aclHelper->apply($tagsQuery)->getScalarResult(), 'name', 'id');
        foreach ($contentTemplatesData as $index => $row) {
            // Replaces tags ids with tags names.
            $contentTemplatesData[$index]['tags'] = array_values(array_intersect_key($tags, array_flip($row['tags'])));
        }

        return $contentTemplatesData;
    }
}
