<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Model\CategoryMaterializedPathModifier;

class CategoryListener
{
    /**
     * @var CategoryMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @param CategoryMaterializedPathModifier $modifier
     */
    public function __construct(CategoryMaterializedPathModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * @param Category $category
     */
    public function postPersist(Category $category)
    {
        $this->modifier->calculateMaterializedPath($category, true);
    }

    /**
     * @param Category           $category
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();
        unset($changeSet['materializedPath']);

        $children = [];
        if ($changeSet) {
            /** @var CategoryRepository $repository */
            $repository = $args->getEntityManager()->getRepository(Category::class);
            $children = $repository->getChildrenWithPath($category);
        }
        $this->modifier->updateMaterializedPathNested($category, $children);
    }
}
