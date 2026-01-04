<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

/**
 * Base class for entities that represent resolved category visibility
 * @method BaseCategoryVisibilityResolved setSourceCategoryVisibility(VisibilityInterface $sourceVisibility = null)
 * @method VisibilityInterface getSourceCategoryVisibility()
 */
#[ORM\MappedSuperclass]
abstract class BaseCategoryVisibilityResolved extends BaseVisibilityResolved
{
    public const SOURCE_STATIC = 1;
    public const SOURCE_PARENT_CATEGORY = 2;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Scope::class)]
    #[ORM\JoinColumn(name: 'scope_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Scope $scope = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Category $category = null;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return Scope
     */
    public function getCope()
    {
        return $this->scope;
    }

    public function setCope(Scope $scope)
    {
        $this->scope = $scope;
    }
}
