<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_test_content_variant")
 */
class TestContentVariant
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_page_product", referencedColumnName="id", nullable=true)
     */
    private $product_page_product;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_page_category", referencedColumnName="id", nullable=true)
     */
    private $category_page_category;

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
    public function getProductPageProduct()
    {
        return $this->product_page_product;
    }

    /**
     * @param Product|null $product_page_product
     */
    public function setProductPageProduct(Product $product_page_product = null)
    {
        $this->product_page_product = $product_page_product;
    }

    /**
     * @return Product
     */
    public function getCategoryPageCategory()
    {
        return $this->category_page_category;
    }

    /**
     * @param Category|null $category_page_category
     */
    public function setCategoryPageCategory(Category $category_page_category = null)
    {
        $this->category_page_category = $category_page_category;
    }
}
