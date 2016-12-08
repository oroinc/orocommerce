<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
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
        $qb = $this->getRootNodesQueryBuilder();
        $qb->andWhere(
            $qb->expr()->eq('node.webCatalog', ':webCatalog')
        );
        $qb->setParameter('webCatalog', $webCatalog);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
