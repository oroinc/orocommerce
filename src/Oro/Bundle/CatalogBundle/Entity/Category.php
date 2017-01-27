<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\CatalogBundle\Model\ExtendCategory;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Component\Tree\Entity\TreeTrait;

/**
 * @ORM\Table(name="oro_catalog_category")
 * @ORM\Entity(repositoryClass="Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository")
 * @Gedmo\Tree(type="nested")
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="slugPrototypes",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_catalog_cat_slug_prototype",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      ),
 *     @ORM\AssociationOverride(
 *          name="slugs",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_catalog_cat_slug",
 *              joinColumns={
 *                  @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(name="slug_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="oro_catalog_category_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-folder"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="catalog"
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
class Category extends ExtendCategory implements SluggableInterface, DatesAwareInterface
{
    use DatesAwareTrait;
    use TreeTrait;
    use SluggableTrait;

    const MATERIALIZED_PATH_DELIMITER = '_';

    const FIELD_PARENT_CATEGORY = 'parentCategory';
    const FIELD_PRODUCTS = 'products';

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
     *      name="oro_catalog_category_title",
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
     * @var Collection|Product[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinTable(
     *      name="oro_category_to_product",
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
     *      name="oro_catalog_cat_short_desc",
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
     *      name="oro_catalog_cat_long_desc",
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
     * @var string
     *
     * @ORM\Column(name="materialized_path", type="string", length=255, nullable=true)
     */
    protected $materializedPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->childCategories = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->shortDescriptions = new ArrayCollection();
        $this->longDescriptions = new ArrayCollection();
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
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
        if (!$this->hasProduct($product)) {
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
        if ($this->hasProduct($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function hasProduct(Product $product)
    {
        return $this->products->contains($product);
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
        return (string)$this->getDefaultTitle();
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

    /**
     *
     * @return string
     */
    public function getMaterializedPath()
    {
        return $this->materializedPath;
    }

    /**
     * @param string $materializedPath
     *
     * @return Category
     */
    public function setMaterializedPath($materializedPath)
    {
        $this->materializedPath = $materializedPath;

        return $this;
    }
}
