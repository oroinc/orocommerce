<?php

namespace Oro\Component\Tree\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository as GedmoNestedTreeRepository;

/**
 * Provides repository functionality for nested tree entities.
 *
 * This repository extends Gedmo's nested tree repository to provide tree structure management capabilities
 * for hierarchical entities. It inherits methods for persisting nodes at specific positions in the tree
 * (as first/last child, as next/previous sibling) and querying tree structures.
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
class NestedTreeRepository extends GedmoNestedTreeRepository
{
}
