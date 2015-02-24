<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="orob2b_catalog_category")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository")
 * @Gedmo\Tree(type="nested")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-folder-open"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Category
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|CategoryTitle[]
     *
     * @ORM\OneToMany(targetEntity="CategoryTitle", mappedBy="category", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $titles;

    /**
     * @var integer
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="left", type="integer")
     */
    protected $left;

    /**
     * @var integer
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="level", type="integer")
     */
    protected $level;

    /**
     * @var integer
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="right", type="integer")
     */
    protected $right;

    /**
     * @var integer
     *
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    protected $root;

    /**
     * @var Category
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="childCategories")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentCategory;

    /**
     * @var Collection|Category[]
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parentCategory", cascade={"ALL"})
     * @ORM\OrderBy({"left" = "ASC"})
     */
    protected $childCategories;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->childCategories = new ArrayCollection();
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|CategoryTitle[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param CategoryTitle $title
     * @return $this
     */
    public function addTitle(CategoryTitle $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setCategory($this);
        }

        return $this;
    }

    /**
     * @param CategoryTitle $title
     * @return $this
     */
    public function removeTitle(CategoryTitle $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param int $left
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param int $right
     * @return $this
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * @return int
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param int $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return Category
     */
    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    /**
     * @param Category $parentCategory
     * @return $this
     */
    public function setParentCategory($parentCategory)
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getChildCategories()
    {
        return $this->titles;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function addChildCategory(Category $category)
    {
        if (!$this->childCategories->contains($category)) {
            $this->childCategories->add($category);
            $category->setParentCategory($this);
        }

        return $this;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function removeChildCategory(Category $category)
    {
        if ($this->childCategories->contains($category)) {
            $this->childCategories->removeElement($category);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
