<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;

/**
* Entity that represents Category Visibility
*
*/
#[ORM\Entity(repositoryClass: CategoryVisibilityRepository::class)]
#[ORM\Table(name: 'oro_category_visibility')]
#[ORM\UniqueConstraint(name: 'oro_ctgr_vis_uidx', columns: ['category_id', 'scope_id'])]
#[Config]
class CategoryVisibility implements VisibilityInterface, ScopeAwareInterface
{
    const PARENT_CATEGORY = 'parent_category';
    const CONFIG = 'config';
    const VISIBILITY_TYPE = 'category_visibility';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Category $category = null;

    #[ORM\Column(name: 'visibility', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $visibility = null;

    #[ORM\ManyToOne(targetEntity: Scope::class)]
    #[ORM\JoinColumn(name: 'scope_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Scope $scope = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @param Category $category
     * @return string
     */
    public static function getDefault($category)
    {
        if ($category instanceof Category && !$category->getParentCategory()) {
            return self::CONFIG;
        } else {
            return self::PARENT_CATEGORY;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Category $category
     * @return array
     */
    public static function getVisibilityList($category)
    {
        $visibilityList = [
            self::PARENT_CATEGORY,
            self::CONFIG,
            self::HIDDEN,
            self::VISIBLE,
        ];
        if ($category instanceof Category && !$category->getParentCategory()) {
            unset($visibilityList[array_search(self::PARENT_CATEGORY, $visibilityList)]);
        }

        return $visibilityList;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntity()
    {
        return $this->getCategory();
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function setTargetEntity($category)
    {
        return $this->setCategory($category);
    }

    /**
     * @param Scope|null $scope
     * @return $this
     */
    public function setScope(Scope $scope = null)
    {
        $this->scope = $scope;

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
     * {@inheritdoc}
     */
    public static function getScopeType()
    {
        return self::VISIBILITY_TYPE;
    }
}
