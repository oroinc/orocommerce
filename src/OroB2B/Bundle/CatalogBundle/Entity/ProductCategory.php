<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(
 *      name="orob2b_catalog_product_ctgry",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_catalog_prod_ctgry_uidx",columns={"product_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\CatalogBundle\Entity\Repository\ProductCategoryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-briefcase"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class ProductCategory
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
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $product;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CatalogBundle\Entity\Category", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    protected $category;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return ProductCategory
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
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
     * @return ProductCategory
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }
}
