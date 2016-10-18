<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

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
}
