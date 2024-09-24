<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerCategoryVisibilityRepository;

/**
* Entity that represents Customer Category Visibility
*
*/
#[ORM\Entity(repositoryClass: CustomerCategoryVisibilityRepository::class)]
#[ORM\Table(name: 'oro_cus_category_visibility')]
#[ORM\UniqueConstraint(name: 'oro_cus_ctgr_vis_uidx', columns: ['category_id', 'scope_id'])]
#[Config]
class CustomerCategoryVisibility implements VisibilityInterface, ScopeAwareInterface
{
    const PARENT_CATEGORY = 'parent_category';
    const CATEGORY = 'category';
    const CUSTOMER_GROUP = 'customer_group';
    const VISIBILITY_TYPE = 'customer_category_visibility';

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
    #[\Override]
    public static function getDefault($category)
    {
        return self::CUSTOMER_GROUP;
    }

    #[\Override]
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    #[\Override]
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Category $category
     * @return array
     */
    #[\Override]
    public static function getVisibilityList($category)
    {
        $visibilityList = [
            self::CUSTOMER_GROUP,
            self::CATEGORY,
            self::PARENT_CATEGORY,
            self::HIDDEN,
            self::VISIBLE,
        ];
        if ($category instanceof Category && !$category->getParentCategory()) {
            unset($visibilityList[array_search(self::PARENT_CATEGORY, $visibilityList)]);
        }
        return $visibilityList;
    }

    /**
     * @return Category
     */
    #[\Override]
    public function getTargetEntity()
    {
        return $this->getCategory();
    }

    /**
     * @param Category $category
     * @return $this
     */
    #[\Override]
    public function setTargetEntity($category)
    {
        return $this->setCategory($category);
    }

    /**
     * @param Scope|null $scope
     * @return $this
     */
    #[\Override]
    public function setScope(Scope $scope = null)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return Scope
     */
    #[\Override]
    public function getScope()
    {
        return $this->scope;
    }

    #[\Override]
    public static function getScopeType()
    {
        return self::VISIBILITY_TYPE;
    }
}
