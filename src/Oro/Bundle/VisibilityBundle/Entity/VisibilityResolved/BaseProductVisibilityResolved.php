<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

/**
 * Represents BaseProductVisibilityResolved class
 *
 * @method BaseProductVisibilityResolved setSourceProductVisibility(VisibilityInterface $sourceProductVisibility = null)
 * @method VisibilityInterface getSourceProductVisibility()
 */
#[ORM\MappedSuperclass]
abstract class BaseProductVisibilityResolved extends BaseVisibilityResolved
{
    const SOURCE_STATIC = 1;
    const SOURCE_CATEGORY = 2;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Scope::class)]
    #[ORM\JoinColumn(name: 'scope_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Scope $scope = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Category $category = null;

    public function __construct(Scope $scope, Product $product)
    {
        $this->scope = $scope;
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return $this
     */
    public function setCategory(?Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
