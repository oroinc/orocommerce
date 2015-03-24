<?php

namespace OroB2B\Bundle\CMSBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

use OroB2B\Bundle\CMSBundle\Entity\Page;

/**
 * @method PageRepository persistAsFirstChildOf() persistAsFirstChildOf(Page $node, Page $parent)
 * @method PageRepository persistAsNextSiblingOf() persistAsNextSiblingOf(Page $node, Page $sibling)
 */
class PageRepository extends NestedTreeRepository
{

}
