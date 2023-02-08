<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * The repository for ContentNode entity.
 *
 * @method $this persistAsFirstChild($node)
 * @method $this persistAsFirstChildOf($node, $parent)
 * @method $this persistAsLastChild($node)
 * @method $this persistAsLastChildOf($node, $parent)
 * @method $this persistAsNextSibling($node)
 * @method $this persistAsNextSiblingOf($node, $sibling)
 * @method $this persistAsPrevSibling($node)
 * @method $this persistAsPrevSiblingOf($node, $sibling)
 */
class ContentNodeRepository extends NestedTreeRepository
{
    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode
     */
    public function getRootNodeByWebCatalog(WebCatalog $webCatalog)
    {
        // Root node fetches without children because
        // in Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver implementations
        // they will be fetched from cache

        $qb = $this->getRootNodesQueryBuilder();
        $qb->andWhere(
            $qb->expr()->eq('node.webCatalog', ':webCatalog')
        );
        $qb->setParameter('webCatalog', $webCatalog);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getRootNodeIdByWebCatalog(WebCatalog|int $webCatalog): ?int
    {
        $qb = $this->getRootNodesQueryBuilder();
        $qb
            ->select('node.id')
            ->andWhere($qb->expr()->eq('node.webCatalog', ':webCatalog'))
            ->setParameter('webCatalog', $webCatalog);

        return $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param WebCatalog $webCatalog
     * @return QueryBuilder
     */
    public function getContentVariantQueryBuilder(WebCatalog $webCatalog)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('node.id as nodeId', 'variant.id as variantId')
            ->from(ContentVariant::class, 'variant')
            ->innerJoin(ContentNode::class, 'node', Join::WITH, 'variant.node = node')
            ->andWhere('node.webCatalog = :webCatalog')
            ->setParameter('webCatalog', $webCatalog);
    }

    /**
     * @param array $ids
     * @return ContentNode[]
     */
    public function getNodesByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('node');
        $qb->andWhere('node.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ContentNode $contentNode
     * @return ContentNode[]
     */
    public function getDirectNodesWithParentScopeUsed(ContentNode $contentNode)
    {
        $qb = $this->getChildrenQueryBuilder($contentNode, true);
        $qb->andWhere(
            $qb->expr()->eq('node.parentScopeUsed', ':parentScopeUsed')
        )
        ->setParameter('parentScopeUsed', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ContentNode|null $parentNode Use null for root level nodes (no parent)
     * @param ContentNode|null $skipNode Use null to get all slug prototypes on the same level
     * @return array|string[]
     */
    public function getSlugPrototypesByParent(?ContentNode $parentNode = null, ?ContentNode $skipNode = null): array
    {
        $qb = $this->createQueryBuilder('node')
            ->select('LOWER(slugPrototype.string) as slug_prototype')
            ->join('node.slugPrototypes', 'slugPrototype');

        if ($parentNode) {
            $qb->where('node.parentNode = :parentNode')
                ->setParameter('parentNode', $parentNode);
        } else {
            $qb->where($qb->expr()->isNull('node.parentNode'));
        }

        if ($skipNode) {
            $qb->andWhere('node != :skipNode')
                ->setParameter('skipNode', $skipNode);
        }

        return array_column($qb->getQuery()->getArrayResult(), 'slug_prototype');
    }

    public function getContentNodePlainTreeQueryBuilder(ContentNode $contentNode, int $treeDepth = -1): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('node');
        $queryBuilder
            ->where($queryBuilder->expr()->eq('node.webCatalog', ':webCatalog'))
            ->setParameter('webCatalog', $contentNode->getWebCatalog()->getId())
            ->andWhere($queryBuilder->expr()->eq('node.root', ':root'))
            ->setParameter('root', $contentNode->getRoot())
            ->andWhere($queryBuilder->expr()->gte('node.left', ':left'))
            ->setParameter('left', $contentNode->getLeft())
            ->andWhere($queryBuilder->expr()->lte('node.right', ':right'))
            ->setParameter('right', $contentNode->getRight())
            ->orderBy('node.left');

        if ($treeDepth > -1) {
            $queryBuilder
                ->andWhere('node.level <= :max_level')
                ->setParameter('max_level', $contentNode->getLevel() + $treeDepth);
        }

        return $queryBuilder;
    }

    /**
     * @param int[] $contentNodeIds
     *
     * @return array<array>
     *  [
     *      [
     *          'id' => int,
     *          'parentNode' => ?array [
     *              'id' => int,
     *          ],
     *          'titles' => [
     *              [
     *                  'id' => int,
     *                  'string' => ?string,
     *                  'fallback' => ?string,
     *                  'localization' => ?array [
     *                      'id' => int,
     *                  ],
     *              ]
     *          ],
     *          'localizedUrls' => [
     *              [
     *                  'id' => int,
     *                  'text' => ?string,
     *                  'fallback' => ?string,
     *                  'localization' => ?array [
     *                      'id' => int,
     *                  ],
     *              ]
     *          ],
     *          'rewriteVariantTitle' => bool,
     *          'left' => int,
     *          'right' => int,
     *          'root' => int,
     *          'level' => int,
     *          // ... Other scalar fields of the ContentNode entity class
     *      ],
     *      // ...
     *  ]
     */
    public function getContentNodesData(array $contentNodeIds): array
    {
        $queryBuilder = $this->createQueryBuilder('node');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('node.id', $contentNodeIds))
            ->leftJoin('node.parentNode', 'parentNode')
            ->addSelect('PARTIAL parentNode.{id}')
            ->innerJoin('node.titles', 'title')
            ->addSelect('PARTIAL title.{id,string,fallback}')
            ->leftJoin('title.localization', 'titleLocalization')
            ->addSelect('PARTIAL titleLocalization.{id}')
            ->leftJoin('node.localizedUrls', 'localizedUrl')
            ->addSelect('PARTIAL localizedUrl.{id,text,fallback}')
            ->leftJoin('localizedUrl.localization', 'urlLocalization')
            ->addSelect('PARTIAL urlLocalization.{id}')
            ->orderBy('node.left')
            ->getQuery()
            ->getArrayResult();
    }
}
