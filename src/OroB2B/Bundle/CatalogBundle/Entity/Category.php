<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CatalogBundle\Model\ExtendCategory;
use OroB2B\Component\Tree\Entity\TreeTrait;

/**
 * @ORM\Table(name="orob2b_catalog_category")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository")
 * @Gedmo\Tree(type="nested")
 * @Config(
 *      routeName="orob2b_catalog_category_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-folder-close"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "activity"={
 *              "show_on_page"="\Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope::UPDATE_PAGE"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Category extends ExtendCategory
{
    use TreeTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_catalog_category_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $titles;

    /**
     * @var Category
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="childCategories")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $parentCategory;

    /**
     * @var Collection|Category[]
     *
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parentCategory", cascade={"persist"})
     * @ORM\OrderBy({"left" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
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

    /**
     * @var Collection|Product[]
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinTable(
     *      name="orob2b_category_to_product",
     *      joinColumns={
     *          @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $products;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_catalog_cat_short_desc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $shortDescriptions;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="orob2b_catalog_cat_long_desc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $longDescriptions;

    /**
     * @var CategoryDefaultProductOptions
     *
     * @ORM\OneToOne(targetEntity="CategoryDefaultProductOptions", cascade={"persist"})
     * @ORM\JoinColumn(name="default_product_options_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $defaultProductOptions;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles            = new ArrayCollection();
        $this->childCategories   = new ArrayCollection();
        $this->products          = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->longDescriptions  = new ArrayCollection();
        $this->createdAt         = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt         = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle()
    {
        $titles = $this->titles->filter(function (LocalizedFallbackValue $title) {
            return null === $title->getLocalization();
        });

        if ($titles->count() != 1) {
            throw new \LogicException('There must be only one default title');
        }

        return $titles->first();
    }

    /**
     * @return Category
     */
    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    /**
     * @param Category|null $parentCategory
     *
     * @return $this
     */
    public function setParentCategory(Category $parentCategory = null)
    {
        $this->parentCategory = $parentCategory;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getChildCategories()
    {
        return $this->childCategories;
    }

    /**
     * @param Category $category
     *
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
     *
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
     *
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
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function removeProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getDefaultTitle()->getString();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortDescriptions()
    {
        return $this->shortDescriptions;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function addShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if (!$this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->add($shortDescription);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $shortDescription
     *
     * @return $this
     */
    public function removeShortDescription(LocalizedFallbackValue $shortDescription)
    {
        if ($this->shortDescriptions->contains($shortDescription)) {
            $this->shortDescriptions->removeElement($shortDescription);
        }

        return $this;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultShortDescription()
    {
        $shortDescription = $this->shortDescriptions->filter(function (LocalizedFallbackValue $shortDescription) {
            return null === $shortDescription->getLocalization();
        });

        if ($shortDescription->count() !== 1) {
            throw new \LogicException('There must be only one default short description');
        }

        return $shortDescription->first();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLongDescriptions()
    {
        return $this->longDescriptions;
    }

    /**
     * @param LocalizedFallbackValue $longDescription
     *
     * @return $this
     */
    public function addLongDescription(LocalizedFallbackValue $longDescription)
    {
        if (!$this->longDescriptions->contains($longDescription)) {
            $this->longDescriptions->add($longDescription);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $longDescription
     *
     * @return $this
     */
    public function removeLongDescription(LocalizedFallbackValue $longDescription)
    {
        if ($this->longDescriptions->contains($longDescription)) {
            $this->longDescriptions->removeElement($longDescription);
        }

        return $this;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultLongDescription()
    {
        $longDescription = $this->longDescriptions->filter(function (LocalizedFallbackValue $longDescription) {
            return null === $longDescription->getLocalization();
        });

        if ($longDescription->count() != 1) {
            throw new \LogicException('There must be only one default long description');
        }

        return $longDescription->first();
    }

    /**
     * @return CategoryDefaultProductOptions
     */
    public function getDefaultProductOptions()
    {
        return $this->defaultProductOptions;
    }

    /**
     * Set unitPrecision
     *
     * @param CategoryDefaultProductOptions $defaultProductOptions
     *
     * @return Category
     */
    public function setDefaultProductOptions(CategoryDefaultProductOptions $defaultProductOptions = null)
    {
        $this->defaultProductOptions = $defaultProductOptions;
    
        return $this;
    }
}
