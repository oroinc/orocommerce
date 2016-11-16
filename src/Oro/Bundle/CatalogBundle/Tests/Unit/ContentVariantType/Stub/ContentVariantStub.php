<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ContentVariantStub implements ContentVariantInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var Category
     */
    protected $categoryPageCategory;
    
    /**
     * @var ArrayCollection|Scope[]
     */
    protected $scopes;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
    }

    /**
     * @return Collection|Scope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function removeScope(Scope $scope)
    {
        if ($this->scopes->contains($scope)) {
            $this->scopes->removeElement($scope);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return 1;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ContentVariantStub
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Category
     */
    public function getCategoryPageCategory()
    {
        return $this->categoryPageCategory;
    }

    /**
     * @param Category $category
     * @return ContentVariantStub
     */
    public function setCategoryPageCategory(Category $category)
    {
        $this->categoryPageCategory = $category;

        return $this;
    }
}
